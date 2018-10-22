<?php
namespace PHPFacile\Group\ByTags\Service;

use PHPFacile\Group\Model\GroupItemInterface;
use PHPFacile\Group\Service\GroupService as DefaultGroupService;

abstract class GroupService extends DefaultGroupService implements GroupServiceInterface
{
    /**
     * Array of tagIds
     *
     * @var $tagsSelectionOrder
     */
    protected $tagsSelectionOrder = [];

    /**
     * Set tags selection order to be used for getNextOptionValues()
     *
     * @param string[] $tagsSelectionOrder Tag ids in expected order
     *
     * @return void
     */
    public function setTagsSelectionOrder($tagsSelectionOrder)
    {
        $this->tagsSelectionOrder = $tagsSelectionOrder;
    }

    /**
     * Checks whether the item of the group must be excluded according to
     * the given filter. The item must be excluded if its tag values doesn't
     * match values provided in filter.
     * FIXME Actually $groupItem should be an implementation of
     * PHPFacile\Group\ByTags\Model\GroupItemInterface not
     * PHPFacile\Group\Model\GroupItemInterface
     *
     * @param GroupItemInterface $groupItem Candidate item of the group
     * @param mixed              $filter    Filter that define the items of the group (Associative array tag id => tag value in basic implementations)
     * @param mixed              $context   Any extra data that might be needed for the processing (not used in this case)
     *
     * @return boolean true if the item must not be considered as part of the group
     */
    protected function isGroupItemToBeFiltered(GroupItemInterface $groupItem, $filter = null, $context = null)
    {
        $this->logger->info(__METHOD__.'(#'.$groupItem->getId().', ..., ...) [Enter]');

        $expectedTags = [];
        if (null !== $filter) {
            if (true === is_array($filter)) {
                // FIXME To be optimized ??
                $filter = json_decode(json_encode($filter));
            }

            if (true === is_object($filter)) {
                if (true === property_exists($filter, 'tags')) {
                    // FIXME To be optimized ??
                    $expectedTags = get_object_vars($filter->tags);
                }
            } else {
                throw new \Exception('Unexcepted $filter type');
            }
        }

        foreach ($expectedTags as $tagId => $tagValue) {
            $tagContent = $groupItem->getTagValue($tagId);
            if (true === is_array($tagContent)) {
                $this->logger->debug(__METHOD__.'(#'.$groupItem->getId().', ..., ...) ('.$tagId.') => ('.$tagValue.' vs '.implode(',', $tagContent).')');
            } else {
                $this->logger->debug(__METHOD__.'(#'.$groupItem->getId().', ..., ...) ('.$tagId.') => ('.$tagValue.' vs '.$tagContent.')');
            }

            if (true === is_array($tagContent)) {
                if (false === in_array($tagValue, $tagContent)) {
                    $this->logger->debug(__METHOD__.'(#'.$groupItem->getId().', ..., ...) [Exit] true ('.$tagId.') => ('.$tagValue.' not in '.implode(',', $tagContent).')');
                    return true;
                }
            } else if ($tagValue != $tagContent) {
                 // REM Do not use strict matching operator in the comparison above
                $this->logger->debug(__METHOD__.'(#'.$groupItem->getId().', ..., ...) [Exit] true ('.$tagId.') => ('.$tagValue.' != '.$tagContent.')');
                return true;
            }
        }

        $this->logger->info(__METHOD__.'(#'.$groupItem->getId().', ..., ...) [Exit] false');
        return false;
    }

    /**
     * Returns the different values found for a given tag within all items of
     * a group (defined by a filter)
     * TAKE CARE: This is not an optimized implemetation. It is highlighly recommended
     * to have a custom implémentation.
     *
     * @param string $tagId   Id of the tag for which we want all the possible values
     * @param mixed  $filter  Filter that define the items of the group (Associative array tag id => tag value in basic implementations)
     * @param mixed  $context Any extra data that might be usefull
     *
     * @return array Tag values
     */
    public function getValuesOfTagInMatchingGroups($tagId, $filter = null, $context = null)
    {
        $this->logger->info(__METHOD__.'('.$tagId.', ..., ...) [Enter]');

        $groupItems = $this->getGroupItems($filter, $context);
        $tagValues  = [];

        foreach ($groupItems as $groupItem) {
            $tagValue = $groupItem->getTagValue($tagId);
            if (true === is_array($tagValue)) {
                foreach ($tagValue as $val) {
                    $tagValues[$val] = $val;
                }

                // $tagValues = array_merge($tagValues, $tagValue)
            } else {
                $tagValues[$tagValue] = $tagValue;
            }
        }

        return $tagValues;
    }

    /**
     * Returns an array of possible values to choose from to select part of
     * the items of the group (using a step by step process, each step is performed
     * by taking the next tag in the order defined by self::setTagsSelectionOrder())
     * Goal: Assuming we've got a group of items matching a filter criteria
     * (ex: places with more than N inhabitants) each of these items have
     * different tag values (others than filter criteria) (ex: continent, country)
     * If we want a user to select one of the items we can ask him to 1st select
     * a tag value (ex: a continent) to reduce the number of items to choose within
     * (and then a country)
     * Example of tagsSelectionOrder [continent, country]
     *
     * @param mixed[] $selectedValuesTagIds Ids of the tag for which a value was previously selected
     * @param mixed   $filter               Filter that define the items of the group (Associative array tag id => tag value in basic implementations)
     * @param mixed   $context              Any extra data that can be usefull
     *
     * @return array Associative array where key is the id of the tag and value an array of possible values for this tag
     */
    public function getNextOptionValues($selectedValuesTagIds = [], $filter = null, $context = null)
    {
        foreach ($this->tagsSelectionOrder as $tagId) {
            if (true === array_key_exists($tagId, $selectedValuesTagIds)) {
                $filter['tags'][$tagId] = $selectedValuesTagIds[$tagId];
            } else {
                // Next tag value to select... is within $tagId list
                return [$tagId => $this->getValuesOfTagInMatchingGroups($tagId, $filter, $context)];
            }
        }

        return [];
    }

    /**
     * Returns a "flat" tree as a tree.
     * Here a a "flat" tree is an array with the keys
     *      "object": a tree node
     *      "sets"  : an array of parent Ids
     *
     * @param array        $flatTree The flat tree to be "converted"
     * @param array|string $treeCfg  Configuration for the tree (as array or json string)
     *                               storeNodeValuesAsArrayKeys : boolean (default false)
     *
     * @return array If storeNodeValuesAsArrayKeys = false, array of StdClass with variables data and children,
     *    where children is an array of stdClass with data and children data as well.
     *    Otherwise [to be completed]
     */
    protected static function flatTree2Tree($flatTree, $treeCfg)
    {
        $storeNodeValuesAsArrayKeys = false;
        if (null !== $treeCfg) {
            if (true === is_array($treeCfg)) {
                $treeCfg = json_decode(json_encode($treeCfg));
            }

            if (true === property_exists($treeCfg, 'storeNodeValuesAsArrayKeys')) {
                $storeNodeValuesAsArrayKeys = $treeCfg->storeNodeValuesAsArrayKeys;
            }
        }

        if (true === $storeNodeValuesAsArrayKeys) {
            $tree = [];
        } else {
            $tree           = new \StdClass();
            $tree->children = [];
        }

        foreach ($flatTree as $flatTreeNode) {
            foreach ($flatTreeNode['sets'] as $set) {
                if (true === $storeNodeValuesAsArrayKeys) {
                    $currentTree = &$tree;
                } else {
                    $currentTree = $tree;
                }

                foreach ($set as $setId) {
                    // New child: $setId
                    if (true === $storeNodeValuesAsArrayKeys) {
                        if (false === array_key_exists($setId, $currentTree)) {
                            $currentTree[$setId] = [];
                        }

                        $currentTree = &$currentTree[$setId];
                    } else {
                        if (false === array_key_exists($setId, $currentTree->children)) {
                            $nodeData     = new \StdClass();
                            $nodeData->id = $setId;

                            $newTreeNode           = new \StdClass();
                            $newTreeNode->data     = $nodeData;
                            $newTreeNode->children = [];
                            $currentTree->children[$setId] = $newTreeNode;
                        }

                        $currentTree = $currentTree->children[$setId];
                    }
                }

                if (true === $storeNodeValuesAsArrayKeys) {
                    $currentTree[] = $flatTreeNode['object'];
                } else {
                    $newTreeNode = new \StdClass();

                    $newTreeNode->data       = $flatTreeNode['object'];
                    $newTreeNode->children   = [];
                    $currentTree->children[] = $newTreeNode;
                }
            }
        }

        return $tree;
    }

    /**
     * Returns items of a group as leaves of a tree where parents are given by their
     * tag values in the expected tag orders.
     *
     * @param string[]     $tagsOrder Ids of the tag in the expected order from root to leaves
     * @param mixed        $filter    Filter that define the items of the group (Associative array tag id => tag value in basic implementations)
     * @param mixed        $context   Any extra data that might help (not used here)
     * @param array|string $treeCfg   Configuration data for the tree Cf. self::flatTree2Tree()
     *
     * @return array Cf. self::flatTree2Tree()
     */
    public function getGroupItemsTree($tagsOrder, $filter = null, $context = null, $treeCfg = null)
    {
        $this->logger->info(__METHOD__.'('.implode(',', $tagsOrder).', ..., ..., ...) [Enter]');
        $groupItems = $this->getGroupItems($filter, $context);

        $flatTree = [];
        foreach ($groupItems as $groupItem) {
            $this->logger->debug(__METHOD__.'('.implode(',', $tagsOrder).', ..., ..., ...) processing groupItem #'.$groupItem->getId());
            $flatTree[] = [
                'object' => $groupItem,
                'sets'   => [[]],
            ];
            foreach ($tagsOrder as $tag) {
                $tagValues = $groupItem->getTagValue($tag);
                if (false === is_array($tagValues)) {
                    $tagValues = [$tagValues];
                }

                // $this->logger->debug(__METHOD__.'('.implode(',',$tagsOrder).', ..., ..., ...) processing groupItem #'.$groupItem->getId().' tag #'.$tag.' values='.implode(',', $tagValues));
                $currentSets = $flatTree[(count($flatTree) - 1)]['sets'];
                $flatTree[(count($flatTree) - 1)]['sets'] = [];
                // TAKE CARE: If $groupItem belongs to several groups may be
                // we don't care about some tagValues and they may cause pbs
                foreach ($tagValues as $tagValue) {
                    foreach ($currentSets as $currentSet) {
                         // FIXME Is it a good idea to hardcode a 'null' string here if $tagValue is null
                        $newSet = array_merge($currentSet, [(true === $tagValue) ?: 'null']);
                        $flatTree[(count($flatTree) - 1)]['sets'][] = $newSet;
                    }
                }
            }
        }

        return self::flatTree2Tree($flatTree, $treeCfg);
    }

}
