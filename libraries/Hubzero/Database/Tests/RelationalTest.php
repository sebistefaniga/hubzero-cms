<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Sam Wilson <samwilson@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 * @since     Class available since release 2.0.0
 */

namespace Hubzero\Database\Tests;

use Hubzero\Test\Database;
use Hubzero\Database\Tests\Mock\User;
use Hubzero\Database\Tests\Mock\Post;

/**
 * Base relational model tests
 */
class RelationalTest extends Database
{
	/**
	 * Sets up the tests...called prior to each test
	 *
	 * @return void
	 * @author 
	 **/
	public function setUp()
	{
		\Hubzero\Database\Relational::setDefaultConnection($this->getMockDriver());
	}

	/**
	 * Tests object construction and variable initialization
	 *
	 * @return void
	 **/
	public function testConstruct()
	{
		$model = User::blank();

		$this->assertInstanceOf('\Hubzero\Database\Relational', $model, 'Model is not an instance of \Hubzero\Database\Relational');
		$this->assertEquals($model->getModelName(), 'User', 'Model should have a model name of "User"');
	}

	/**
	 * Tests to make sure a call to a helper function actually finds the function
	 *
	 * @return void
	 **/
	public function testCallHelper()
	{
		$this->assertEquals('Test', User::one(1)->getFirstName(), 'Model should have returned a first name of "Test"');
	}

	/**
	 * Tests to make sure a call to a transformer actually finds the transformer
	 *
	 * @return void
	 **/
	public function testCallTransformer()
	{
		$this->assertEquals('Tester', User::one(1)->nickname, 'Model should have returned a nickname of "Tester"');
	}

	/**
	 * Tests to make sure that a result can be retrieved
	 *
	 * @return void
	 **/
	public function testOneReturnsResult()
	{
		$this->assertEquals(1, User::one(1)->id, 'Model should have returned an instance with id of 1');
	}

	/**
	 * Tests that a call for a non-existant row via oneOrFail method throws an exception
	 *
	 * @expectedException RuntimeException
	 * @return void
	 **/
	public function testOneOrFailThrowsException()
	{
		User::oneOrFail(0);
	}

	/**
	 * Tests that a request for a non-existant row via oneOrNew method returns new model
	 *
	 * @return void
	 **/
	public function testOneOrNewCreatesNew()
	{
		$this->assertTrue(User::oneOrNew(0)->isNew(), 'Model should have stated that it was new');
	}

	/**
	 * Tests that a belongsToOne relationship properly grabs the related side of the relationship
	 *
	 * @return void
	 **/
	public function testBelongsToOneReturnsRelationship()
	{
		$this->assertEquals(1, Post::oneOrFail(1)->user->id, 'Model should have returned a user id of 1');
	}

	/**
	 * Tests that the belongs to one relationship can properly constrain the belongs to side
	 *
	 * @return void
	 **/
	public function testBelongsToOneCanBeConstrained()
	{
		// Get all users that have 2 or more posts - this should return 1 result
		$posts = Post::all()->whereRelatedHas('user', function($user)
		{
			$user->whereEquals('name', 'Test User');
		})->rows();

		$this->assertCount(2, $posts, 'Model should have returned a count of 2 posts for the user by the name of "Test User"');
	}

	/**
	 * Tests that a oneToMany relationship properly grabs the many side of the relationship
	 *
	 * @return void
	 **/
	public function testOneToManyReturnsRelationship()
	{
		$this->assertCount(2, User::oneOrFail(1)->posts, 'Model should have returned a count of 2 posts for user 1');
	}

	/**
	 * Tests that the one side of the relationship can be properly constrained by the many side
	 *
	 * @return void
	 **/
	public function testOneToManyCanBeConstrainedByCount()
	{
		// Get all users that have 2 or more posts - this should return 1 result
		$users = User::all()->whereRelatedHasCount('posts', 2)->rows();

		$this->assertCount(1, $users, 'Model should have returned a count of 1 user with 2 or more posts');
	}

	/**
	 * Tests that a manyToMany relationship properly grabs the many side of the relationship
	 *
	 * @return void
	 **/
	public function testManyToManyReturnsRelationship()
	{
		$this->assertCount(3, Post::oneOrFail(1)->tags, 'Model should have returned a count of 3 tags for post 1');
	}

	/**
	 * Tests that the local/left side of the m2m relationship can be properly constrained by the related/right side
	 *
	 * @return void
	 **/
	public function testManyToManyCanBeConstrainedByCount()
	{
		$posts = Post::all()->whereRelatedHasCount('tags', 3)->rows();

		$this->assertCount(1, $posts, 'Model should have returned a count of 1 post with 3 or more tags');
	}

	/**
	 * Tests that the local/left side of the m2m relationship can be properly constrained by the related/right side
	 *
	 * @return void
	 **/
	public function testManyToManyCanBeConstrained()
	{
		$posts = Post::all()->whereRelatedHas('tags', function($tags)
		{
			$tags->whereEquals('name', 'fun stuff');
		})->rows();

		$this->assertCount(1, $posts, 'Model should have returned a count of 1 post with the tag "fun stuff"');
	}

	/**
	 * Tests that an including call can properly preload a simple one to many relationship
	 *
	 * @return void
	 **/
	public function testIncludingOneToManyPreloadsRelationship()
	{
		$users = User::all()->including('posts')->rows()->first();

		$this->assertNotNull($users->getRelationship('posts'), 'Model should have had a relationship named posts defined');
	}

	/**
	 * Tests that an including call can properly preload a simple many to many relationship
	 *
	 * @return void
	 **/
	public function testIncludingManyToManyPreloadsRelationship()
	{
		$posts = Post::all()->including('tags')->rows()->first();

		$this->assertNotNull($posts->getRelationship('tags'), 'Model should have had a relationship named tags defined');
	}

	/**
	 * Tests that an including call can be constrained on a one to many relationship
	 *
	 * @return void
	 **/
	public function testIncludingOneToManyCanBeConstrained()
	{
		$users = User::all()->including(['posts', function($posts)
		{
			$posts->where('content', 'LIKE', '%computer%');
		}])->rows();

		$this->assertCount(1, $users->seek(1)->posts, 'Model should have had 1 post that met the constraint');
		$this->assertCount(0, $users->seek(2)->posts, 'Model should have had 0 posts that met the constraint');
	}
}