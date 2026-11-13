<?php

namespace App\Cms\Extensions;

use RuntimeException;

/** A package (zip or folder) that can't be installed. The message is shown to the admin. */
class InvalidPackage extends RuntimeException {}
