<?php

/**
 * MIT License. This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Propel\Tests\Generator\Behavior\Sortable;

use Propel\Runtime\Exception\PropelException;
use Propel\Tests\Bookstore\Behavior\Map\SortableTable11TableMap;
use Propel\Tests\Bookstore\Behavior\SortableTable11 as Table11;
use Propel\Tests\Bookstore\Behavior\SortableTable11Query;

/**
 * Tests for SortableBehavior class
 *
 * @author Massimiliano Arione
 *
 * @group database
 */
class SortableBehaviorObjectBuilderModifierTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->populateTable11();
    }

    /**
     * @return void
     */
    public function testPreInsert()
    {
         SortableTable11TableMap::doDeleteAll();
        $t1 = new Table11();
        $t1->save();
        $this->assertEquals($t1->getRank(), 1, 'Sortable inserts new line in first position if no row present');
        $t2 = new Table11();
        $t2->setTitle('row2');
        $t2->save();
        $this->assertEquals($t2->getRank(), 2, 'Sortable inserts new line in last position');
    }

    /**
     * @return void
     */
    public function testPreDelete()
    {
        $max = SortableTable11Query::create()->getMaxRank();
        $t3 = SortableTable11Query::retrieveByRank(3);
        $t3->delete();
        $this->assertEquals($max - 1, SortableTable11Query::create()->getMaxRank(), 'Sortable rearrange subsequent rows on delete');
        $t4 = SortableTable11Query::create()->filterByTitle('row4')->findOne();
        $this->assertEquals(3, $t4->getRank(), 'Sortable rearrange subsequent rows on delete');
    }

    /**
     * @return void
     */
    public function testIsFirst()
    {
        $first = SortableTable11Query::retrieveByRank(1);
        $middle = SortableTable11Query::retrieveByRank(2);
        $last = SortableTable11Query::retrieveByRank(4);
        $this->assertTrue($first->isFirst(), 'isFirst() returns true for the first in the rank');
        $this->assertFalse($middle->isFirst(), 'isFirst() returns false for a middle rank');
        $this->assertFalse($last->isFirst(), 'isFirst() returns false for the last in the rank');
    }

    /**
     * @return void
     */
    public function testIsLast()
    {
        $first = SortableTable11Query::retrieveByRank(1);
        $middle = SortableTable11Query::retrieveByRank(2);
        $last = SortableTable11Query::retrieveByRank(4);
        $this->assertFalse($first->isLast(), 'isLast() returns false for the first in the rank');
        $this->assertFalse($middle->isLast(), 'isLast() returns false for a middle rank');
        $this->assertTrue($last->isLast(), 'isLast() returns true for the last in the rank');
    }

    /**
     * @return void
     */
    public function testGetNext()
    {
        $t = SortableTable11Query::retrieveByRank(3);
        $this->assertEquals(4, $t->getNext()->getRank(), 'getNext() returns the next object in rank');

        $t = SortableTable11Query::retrieveByRank(4);
        $this->assertNull($t->getNext(), 'getNext() returns null for the last object');
    }

    /**
     * @return void
     */
    public function testGetPrevious()
    {
        $t = SortableTable11Query::retrieveByRank(3);
        $this->assertEquals(2, $t->getPrevious()->getRank(), 'getPrevious() returns the previous object in rank');

        $t = SortableTable11Query::retrieveByRank(1);
        $this->assertNull($t->getPrevious(), 'getPrevious() returns null for the first object');
    }

    /**
     * @return void
     */
    public function testInsertAtRank()
    {
        $t = new Table11();
        $t->setTitle('new');
        $t->insertAtRank(2);
        $this->assertEquals(2, $t->getRank(), 'insertAtRank() sets the position');
        $this->assertTrue($t->isNew(), 'insertAtRank() doesn\'t save the object');
        $t->save();
        $expected = [1 => 'row1', 2 => 'new', 3 => 'row2', 4 => 'row3', 5 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtRank() shifts the entire suite');
    }

    /**
     * @return void
     */
    public function testInsertAtMaxRankPlusOne()
    {
        $t = new Table11();
        $t->setTitle('new');
        $t->insertAtRank(5);
        $this->assertEquals(5, $t->getRank(), 'insertAtRank() sets the position');
        $t->save();
        $expected = [1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4', 5 => 'new'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtRank() can insert an object at the end of the list');
    }

    /**
     * @return void
     */
    public function testInsertAtNegativeRank()
    {
        $this->expectException(PropelException::class);

        $t = new Table11();
        $t->insertAtRank(0);
    }

    /**
     * @return void
     */
    public function testInsertAtOverMaxRank()
    {
        $this->expectException(PropelException::class);

        $t = new Table11();
        $t->insertAtRank(6);
    }

    /**
     * @return void
     */
    public function testInsertAtBottom()
    {
        $t = new Table11();
        $t->setTitle('new');
        $t->insertAtBottom();
        $this->assertEquals(5, $t->getRank(), 'insertAtBottom() sets the position to the last');
        $this->assertTrue($t->isNew(), 'insertAtBottom() doesn\'t save the object');
        $t->save();
        $expected = [1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4', 5 => 'new'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtBottom() does not shift the entire suite');
    }

    /**
     * @return void
     */
    public function testInsertAtTop()
    {
        $t = new Table11();
        $t->setTitle('new');
        $t->insertAtTop();
        $this->assertEquals(1, $t->getRank(), 'insertAtTop() sets the position to 1');
        $this->assertTrue($t->isNew(), 'insertAtTop() doesn\'t save the object');
        $t->save();
        $expected = [1 => 'new', 2 => 'row1', 3 => 'row2', 4 => 'row3', 5 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'insertAtTop() shifts the entire suite');
    }

    /**
     * @return void
     */
    public function testMoveToRank()
    {
        $t2 = SortableTable11Query::retrieveByRank(2);
        $t2->moveToRank(3);
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move up');
        $t2->moveToRank(1);
        $expected = [1 => 'row2', 2 => 'row1', 3 => 'row3', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move to the first rank');
        $t2->moveToRank(4);
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move to the last rank');
        $t2->moveToRank(2);
        $expected = [1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToRank() can move down');
    }

    /**
     * @return void
     */
    public function testMoveToNewObject()
    {
        $this->expectException(PropelException::class);

        $t = new Table11();
        $t->moveToRank(2);
    }

    /**
     * @return void
     */
    public function testMoveToNegativeRank()
    {
        $this->expectException(PropelException::class);

        $t = SortableTable11Query::retrieveByRank(2);
        $t->moveToRank(0);
    }

    /**
     * @return void
     */
    public function testMoveToOverMaxRank()
    {
        $this->expectException(PropelException::class);

        $t = SortableTable11Query::retrieveByRank(2);
        $t->moveToRank(5);
    }

    /**
     * @return void
     */
    public function testSwapWith()
    {
        $t2 = SortableTable11Query::retrieveByRank(2);
        $t4 = SortableTable11Query::retrieveByRank(4);
        $t2->swapWith($t4);
        $expected = [1 => 'row1', 2 => 'row4', 3 => 'row3', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'swapWith() swaps ranks of the two objects and leaves the other ranks unchanged');
    }

    /**
     * @return void
     */
    public function testMoveUp()
    {
        $t3 = SortableTable11Query::retrieveByRank(3);
        $res = $t3->moveUp();
        $this->assertEquals($t3, $res, 'moveUp() returns the current object');
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() swaps ranks with the object of higher rank');
        $t3->moveUp();
        $expected = [1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() swaps ranks with the object of higher rank');
        $res = $t3->moveUp();
        $expected = [1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveUp() changes nothing when called on the object at the top');
    }

    /**
     * @return void
     */
    public function testMoveDown()
    {
        $t2 = SortableTable11Query::retrieveByRank(2);
        $res = $t2->moveDown();
        $this->assertEquals($t2, $res, 'moveDown() returns the current object');
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() swaps ranks with the object of lower rank');
        $t2->moveDown();
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() swaps ranks with the object of lower rank');
        $res = $t2->moveDown();
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveDown() changes nothing when called on the object at the bottom');
    }

    /**
     * @return void
     */
    public function testMoveToTop()
    {
        $t3 = SortableTable11Query::retrieveByRank(3);
        $res = $t3->moveToTop();
        $this->assertEquals($t3, $res, 'moveToTop() returns the current oobject');
        $expected = [1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToTop() moves to the top');
        $res = $t3->moveToTop();
        $expected = [1 => 'row3', 2 => 'row1', 3 => 'row2', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToTop() changes nothing when called on the top node');
    }

    /**
     * @return void
     */
    public function testMoveToBottom()
    {
        $t2 = SortableTable11Query::retrieveByRank(2);
        $res = $t2->moveToBottom();
        $this->assertEquals($t2, $res, 'moveToBottom() returns the current object');
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToBottom() moves to the bottom');
        $res = $t2->moveToBottom();
        $this->assertNull($res, 'moveToBottom() returns null when called on the bottom node');
        $expected = [1 => 'row1', 2 => 'row3', 3 => 'row4', 4 => 'row2'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'moveToBottom() changes nothing when called on the bottom node');
    }

    /**
     * @return void
     */
    public function testRemoveFromList()
    {
        $t2 = SortableTable11Query::retrieveByRank(2);
        $res = $t2->removeFromList();
        $this->assertTrue($res instanceof Table11, 'removeFromList() returns the current object');
        $this->assertNull($res->getRank(), 'removeFromList() resets the object\'s rank');
         SortableTable11TableMap::clearInstancePool();
        $expected = [1 => 'row1', 2 => 'row2', 3 => 'row3', 4 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'removeFromList() does not change the list until the object is saved');
        $t2->save();
         SortableTable11TableMap::clearInstancePool();
        $expected = [null => 'row2', 1 => 'row1', 2 => 'row3', 3 => 'row4'];
        $this->assertEquals($expected, $this->getFixturesArray(), 'removeFromList() changes the list once the object is saved');
    }
}
