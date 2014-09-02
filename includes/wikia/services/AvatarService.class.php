<?php
class AvatarService extends Service {

	const AVATAR_SIZE_SMALL = 20;
	const AVATAR_SIZE_MEDIUM = 50;
	const AVATAR_SIZE_LARGE = 150;

	// When avatar's width/height is greater than 20px, it will be smaller
	// after converting to JPGs from original format (PNGs / GIFs).
	// It was measured in DAR-1935 (Jira).
	const PERFORMANCE_JPEG_THRESHOLD = 20;

	/**
	 * Internal method for getting user object with caching
	 */
	static private function getUser($userName) {
		wfProfileIn(__METHOD__);

		static $usersCache;

		if( isset($usersCache[$userName]) ) {
			$user = $usersCache[$userName];
		} else {
			$user = User::newFromName($userName);

			$usersCache[$userName] = $user;
		}

		wfProfileOut(__METHOD__);
		return $user;
	}

	/**
	 * Get URL to default avatar
	 */
	static public function getDefaultAvatar($avatarSize) {
		wfProfileIn(__METHOD__);
		global $wgStylePath;

		if (class_exists('Masthead')) {
			$avatarUrl = Masthead::newFromUserId(0)->getThumbnail($avatarSize);
		}
		else {
			$randomInt = rand(1, 3);
			$avatarUrl = "{$wgStylePath}/oasis/images/generic_avatar{$randomInt}.png";
		}

		wfProfileOut(__METHOD__);
		return $avatarUrl;
	}

	/**
	 * Get URL to user page / Special:Contributions
	 * Accepts a namespace constant to support alternate user pages, like blogs.
	 * @param  string $userName
	 * @param  int    $ns
	 * @return string $url
	 */
	static function getUrl($userName, $ns = NS_USER) {
		wfProfileIn(__METHOD__);

		static $linksCache;

		$url = '';
		
		$cacheSignature = "{$userName}_{$ns}";

		if( isset($linksCache[$cacheSignature]) ) {
			$url = $linksCache[$cacheSignature];
		} else {
			if (User::isIP($userName)) {
				// anon: point to Special:Contributions
				$url = Skin::makeSpecialUrl('Contributions').'/'.$userName;
			}
			else {
				// user: point to user page
				$userPage = Title::newFromText($userName, $ns);
				if ( !is_null( $userPage ) ) {
					$url = $userPage->getLocalUrl();
				}
			}

			$linksCache[$cacheSignature] = $url;
		}

		wfProfileOut(__METHOD__);
		return $url;
	}

	/**
	 * Get URL for avatar
	 *
	 * @param string|User $user user name
	 * @param int $avatarSize
	 * @return String avatar's URL
	 */
	static function getAvatarUrl($user, $avatarSize = 20) {
		wfProfileIn(__METHOD__);

		static $avatarsCache;
		if( $user instanceof User ) {
			$key = "{$user->getName()}::{$avatarSize}";
		} else {
			//assumes $user is a string with user name
			$key = "{$user}::{$avatarSize}";
		}

		if( isset($avatarsCache[$key]) ) {
			$avatarUrl = $avatarsCache[$key];
		} else {
			if( !($user instanceof User) ) {
				$user = self::getUser($user);
			}

			// handle anon users - return default avatar
			if( empty($user) || !class_exists('Masthead') ) {
				$avatarUrl = self::getDefaultAvatar($avatarSize);

				wfProfileOut(__METHOD__);
				return $avatarUrl;
			}

			$masthead = Masthead::newFromUser($user);
			$avatarUrl = $masthead->getThumbnailPurgeUrl($avatarSize);

			// use per-user cachebuster when custom avatar is used
			$cb = !$masthead->isDefault() ? intval($user->getOption('avatar_rev')) : 0;

			// Make URLs consistent and using no-cookie domain.  We need to pass a
			// stringified zero rather than an actual zero because this function
			// treats them differently o_O  Setting this to string zero matches
			// the anonymous user behavior (BugId:22190)
			$avatarUrl = wfReplaceImageServer($avatarUrl,  ($cb > 0) ? $cb : "0");

			// make avatars as JPG intead of PNGs / GIF but only when it will be a gain (most likely)
			if (intval($avatarSize) > self::PERFORMANCE_JPEG_THRESHOLD) {
				$avatarUrl = ImagesService::overrideThumbnailFormat($avatarUrl, ImagesService::EXT_JPG);
			}

			$avatarsCache[$key] = $avatarUrl;
		}

		wfProfileOut(__METHOD__);
		return $avatarUrl;
	}

	/**
	 * Render avatar
	 */
	static function renderAvatar($userName, $avatarSize = 20) {
		wfProfileIn(__METHOD__);

		// For performance reasons, we only generate avatars that are of specific sizes.
		// We allow HTML tag to resize to any size.
		if ($avatarSize <= self::AVATAR_SIZE_SMALL) {
			$allowedSize = self::AVATAR_SIZE_SMALL;
		} else if ($avatarSize <= self::AVATAR_SIZE_MEDIUM) {
			$allowedSize = self::AVATAR_SIZE_MEDIUM;
		} else {
			$allowedSize = self::AVATAR_SIZE_LARGE;
		}

		$avatarUrl = self::getAvatarUrl($userName, $allowedSize);

		$ret = Xml::element('img', array(
			'src' => $avatarUrl,
			'width' => $avatarSize,
			'height' => $avatarSize,
			'class' => 'avatar',
			'alt' => $userName,
		));

		wfProfileOut(__METHOD__);
		return $ret;
	}

	/**
	 * Render link to user page / Special:Contributions
	 */
	static function renderLink($userName) {
		wfProfileIn(__METHOD__);

		// for anons show "A Wikia user" instead of IP address
		if (User::isIP($userName)) {
			$label = wfMsg('oasis-anon-user');
		}
		else {
			$label = $userName;
		}

		$link = Xml::element('a',
			array('href' => self::getUrl($userName)),
			$label);

		wfProfileOut(__METHOD__);
		return $link;
	}

	/**
	 * Render avatar and link to user page
	 */
	static function render($userName, $avatarSize = 20) {
		wfProfileIn(__METHOD__);

		// render avatar
		$ret = self::renderAvatar($userName, $avatarSize);

		// add small spacing
		$ret .= ' ';

		// render link to user page / Special:Contributions
		$ret .= self::renderLink($userName);

		wfProfileOut(__METHOD__);
		return $ret;
	}

	/**
	 * isEmptyOrFirstDefault -- check if the user has set none or uses the first of the default avatars
	 */
	static public function isEmptyOrFirstDefault( $userName ) {
		wfProfileIn( __METHOD__ );
		global $wgStylePath;

		if ( class_exists( 'Masthead' ) ) {
			$avatarUrl = Masthead::newFromUserName($userName)->mUser->getOption( AVATAR_USER_OPTION_NAME );
			$images = getMessageForContentAsArray( 'blog-avatar-defaults' );
			$firstDefaultImage = $images[ 0 ];
			if( empty( $avatarUrl ) || substr( $avatarUrl, -strlen( $firstDefaultImage ) ) === $firstDefaultImage ) {
				wfProfileOut( __METHOD__ );
				return true;
			}
		} else {
			$avatarUrl = self::getAvatarUrl( $userName );
			var_dump( $avatarUrl );
			$avatarDefaultUrlStart = "{$wgStylePath}/oasis/images/generic_avatar";
			var_dump($avatarDefaultUrlStart);
			if( $avatarUrl === '' || strpos( $avatarUrl, $avatarDefaultUrlStart ) === 0 ) {
				wfProfileOut( __METHOD__ );
				return true;
			}
		}

		wfProfileOut( __METHOD__ );
		return false;
	}
}
