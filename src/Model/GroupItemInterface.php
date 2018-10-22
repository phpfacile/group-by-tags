<?php
namespace PHPFacile\Group\ByTags\Model;

use PHPFacile\Group\Model\GroupItemInterface as DefaultGroupItemInterface;

interface GroupItemInterface extends DefaultGroupItemInterface
{
    /**
     * Set a value to a tag
     *
     * @param string $tagId    Id of the tag
     * @param mixed  $tagValue Value of the tag
     *
     * @return void
     */
    public function setTagValue($tagId, $tagValue);

    /**
     * Add a value to a tag being an array of values
     *
     * @param string $tagId    Id of the tag
     * @param mixed  $tagValue Value of the tag
     *
     * @return void
     */
    public function addTagValue($tagId, $tagValue);

    /**
     * Returns value of a tag
     *
     * @param string $tagId Id of the tag
     *
     * @return mixed
     */
    public function getTagValue($tagId);
}
