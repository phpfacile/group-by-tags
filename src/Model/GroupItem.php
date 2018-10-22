<?php
namespace PHPFacile\Group\ByTags\Model;

use PHPFacile\Group\Model\GroupItem as DefaultGroupItem;

class GroupItem extends DefaultGroupItem
{
    /**
     * Associative array of tag id => tag value(s)
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Set a value to a tag
     *
     * @param string $tagId    Id of the tag
     * @param mixed  $tagValue Value of the tag
     *
     * @return void
     */
    public function setTagValue($tagId, $tagValue)
    {
        $this->tags[$tagId] = $tagValue;
    }

    /**
     * Add a value to a tag being an array of values
     *
     * @param string $tagId    Id of the tag
     * @param mixed  $tagValue Value of the tag
     *
     * @return void
     */
    public function addTagValue($tagId, $tagValue)
    {
        if (false === array_key_exists($tagId, $this->tags)) {
            $this->tags[$tagId] = [];
        }

        $this->tags[$tagId][] = $tagValue;
    }

    /**
     * Returns value of a tag
     *
     * @param string $tagId Id of the tag
     *
     * @return mixed
     */
    public function getTagValue($tagId)
    {
        // TODO Add lazy loading ???
        return $this->tags[$tagId];
    }

}
