<?php

namespace Econtech\TestFairy\Tests;

use Econtech\TestFairy\TrelloReporter;

class TrelloReporterTest extends \PHPUnit_Framework_TestCase
{

    protected $key      = 'a287836405f73a08dc05c7564add7323';
    protected $token    = '3a2c4fc71ef61ec8bfcea9f9a1f115166d2f438de809f596e86591b42094b771';
    protected $project  = 'Bugs';


    public function testReporter()
    {

        $faker = \Faker\Factory::create();

        $reporter = new TrelloReporter($this->key, $this->token);

        $projects = $reporter->getProjects();

        $this->assertInternalType('array', $projects);
        $this->assertNotEmpty($projects);

        // method to get url, that must be sent to user to generate token for him

        $this->assertInternalType('string', $reporter->getAuthorizeUrl());

        $reporter = new TrelloReporter($this->key, $this->token, $this->project);

        $this->assertNotFalse($reporter);

        // testing lists

        $lists = $reporter->getLists();

        $this->assertInternalType('array', $lists);

        // testing issue creation

        $issueTitle = $faker->text(100);
        $issueBody  = $faker->text(400);

        $issue = $reporter->createIssue($issueTitle, $issueBody);

        $this->assertNotFalse($issue);

        $this->assertNotEmpty($issue['name']);

        $this->assertEquals($issue['name'], $issueTitle);

        $issueId = $issue['id'];


        // testing getTypes

        $this->assertInternalType('array', $reporter->getTypes());


        // testing issues loading

        $issues = $reporter->getIssues();

        $this->assertNotEmpty($issues);


        // looking for newly created issue

        $found = false;
        foreach ($issues as $issue) {
            if ($issue == $issueId) {
                $found = true;
            }
        }

        $this->assertTrue($found);


        // checking status

        $issue = $reporter->getSummary(null, $issueId);

        $this->assertNotEmpty($issue);

        $this->assertEquals($reporter->getStatus(null, $issueId), $issue['list']['name']);


        // check original content

        $this->assertEquals($issue['name'], $issueTitle);

        $description = $reporter->getDescription(null, $issueId);

        $this->assertEquals($description, $issueBody);


        // updating issue

        $newBody = $faker->text(500);

        $reporter->editIssue(null, $issueId, $newBody);

        $issue = $reporter->getSummary(null, $issueId);

        $this->assertEquals($issue['desc'], $newBody);

        $this->assertNotEquals($issue['desc'], $issueBody);

    }
}
