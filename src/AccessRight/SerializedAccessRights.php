<?php

namespace App\AccessRight;

/**
 * This interface is used by SerializationListener to automatically add the user access rights
 * during the entity serialization.
 *
 * @see App\Listener\SerializationListener
 */
interface SerializedAccessRights
{
}
