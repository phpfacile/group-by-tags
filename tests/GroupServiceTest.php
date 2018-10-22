<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use PHPFacile\Group\ByTags\Service\GroupService;
use PHPFacile\Group\ByTags\Model\GroupItem;

use Zend\Log\Writer\Noop;
use Zend\Log\Logger;
use Zend\Log\PsrLoggerAdapter;

final class GroupServiceTest extends TestCase
{
    protected $groupService;


    protected function setUp()
    {
        $writer = new Noop();
        $logger = new Logger();
        $logger->addWriter($writer);
        $logger = new PsrLoggerAdapter($logger);

        $groupService = new CustomGroupService();
        $groupService->setLogger($logger);
        $this->groupService = $groupService;

        $this->logger = $logger;
    }

    public function testGetGroupTree()
    {
        $filter = null;
        $context = null;
        $cfg = ['storeNodeValuesAsArrayKeys' => true];
        $tree = $this->groupService->getGroupItemsTree(['category', 'sub-category'], $filter, $context, $cfg);
        $this->assertEquals([1, 2, 3], array_keys($tree));
        $this->assertEquals([5, 4], array_keys($tree[1]));
        $this->assertEquals([3], array_keys($tree[2]));
        $this->assertEquals([2, 1], array_keys($tree[3]));

        $cfg = null;
        $tree = $this->groupService->getGroupItemsTree(['category', 'sub-category'], $filter, $context, $cfg);
        //$this->assertEquals(3, count($tree->children));
        //$this->assertEquals(2, count($tree->children[1]->children));
        //$this->assertEquals(1, count($tree->children[2]->children));
        //$this->assertEquals(2, count($tree->children[3]->children));
        $this->assertEquals([1, 2, 3], array_keys($tree->children));
        $this->assertEquals([5, 4], array_keys($tree->children[1]->children));
        $this->assertEquals([3], array_keys($tree->children[2]->children));
        $this->assertEquals([2, 1], array_keys($tree->children[3]->children));

        $this->assertEquals(2, count($tree->children[1]->children[5]->children));
        $this->assertEquals(1, $tree->children[1]->children[5]->children[0]->data->getId());


        $groupService = new CountryLakesGroupService();
        $groupService->setLogger($this->logger);
        $tree = $groupService->getGroupItemsTree(
            ['category'],
            [
                'tags' => ['country' => 'Bisounours world']
            ],
            null,
            null
        );
        // Here data are successfully filtered using country tag
        $this->assertEquals(
            [],
            array_keys($tree->children)
        );

        $groupService = new CountryLakesGroupService();
        $groupService->setLogger($this->logger);
        $tree = $groupService->getGroupItemsTree(
            ['category'],
            [
                'tags' => ['country' => 'Peru']
            ],
            null,
            null
        );
        // REM: This is a consistent output according to the provided data
        // but this shows that using tags without hierarchy is not a good
        // strategy
        // We would expected that the provided data allows to only return
        // "Lakes of Peru"
        $this->assertEquals(
            ['Lakes of Peru', 'Lakes of Bolivia'],
            array_keys($tree->children)
        );
    }
}


class CustomGroupService extends GroupService
{
    public function getAllGroupItems()
    {
        $groupItems = [];

        $groupItem = new GroupItem();
        $groupItem->setId(1);
        $groupItem->setTagValue('category', 1);
        $groupItem->setTagValue('sub-category', 5);
        $groupItems[] = $groupItem;

        $groupItem = new GroupItem();
        $groupItem->setId(2);
        $groupItem->setTagValue('category', 1);
        $groupItem->setTagValue('sub-category', 5);
        $groupItems[] = $groupItem;

        $groupItem = new GroupItem();
        $groupItem->setId(3);
        $groupItem->setTagValue('category', 1);
        $groupItem->setTagValue('sub-category', 4);
        $groupItems[] = $groupItem;

        $groupItem = new GroupItem();
        $groupItem->setId(4);
        $groupItem->setTagValue('category', 2);
        $groupItem->setTagValue('sub-category', 3);
        $groupItems[] = $groupItem;

        $groupItem = new GroupItem();
        $groupItem->setId(5);
        $groupItem->setTagValue('category', 2);
        $groupItem->setTagValue('sub-category', 3);
        $groupItems[] = $groupItem;

        $groupItem = new GroupItem();
        $groupItem->setId(6);
        $groupItem->setTagValue('category', 3);
        $groupItem->setTagValue('sub-category', 2);
        $groupItems[] = $groupItem;

        $groupItem = new GroupItem();
        $groupItem->setId(7);
        $groupItem->setTagValue('category', 3);
        $groupItem->setTagValue('sub-category', 1);
        $groupItems[] = $groupItem;

        return $groupItems;
    }

}

/**
 * In some cases we can have a groupItem that belongs to 2 groups
 * all of the 2 groups belonging to 2 different parent groups
 * Ex:
 * Peru > lakes of Peru > Titicaca lake
 * Bolivia > lakes of Bolivia > Titicaca lake
 * REM: Not sure the country level is required for the demonstration
 * In such a case when we want all items of Peru
 * we will get "Titicaca lake" but we have to take care when we have to manage
 * the groups. Indeed, in such a case the GroupItem:"Titicaca lake" got
 * 2 values for the "group Tag" ("lakes of Peru" and "lakes of Bolivia").
 * If we are only interested in data related to "Peru" we would have to ignore
 * "lakes of Bolivia"
 */
class CountryLakesGroupService extends GroupService
{
    public function getAllGroupItems()
    {
        $groupItems = [];

        $groupItem = new GroupItem();
        $groupItem->setId('Titicaca Lake');
        $groupItem->addTagValue('category', 'Lakes of Peru');
        $groupItem->addTagValue('category', 'Lakes of Bolivia');
        $groupItem->addTagValue('country', 'Peru');
        $groupItem->addTagValue('country', 'Bolivia');
        $groupItems[] = $groupItem;

        return $groupItems;
    }
}
