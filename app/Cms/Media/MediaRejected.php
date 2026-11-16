<?php

namespace App\Cms\Media;

use RuntimeException;

/** A file the media library does not accept; the message says why, in words for the person who uploaded it. */
class MediaRejected extends RuntimeException {}
