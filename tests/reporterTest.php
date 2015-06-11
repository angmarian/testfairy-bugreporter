<?php

class reporterTest extends PHPUnit_Framework_TestCase
{

    protected $token   = "98b5e75ff2925166b61d7464d964f9b5";
    protected $project = "My Sample Project";


    public function testReporter()
    {

        $faker = Faker\Factory::create();

        // testing connection

        $reporter = new PivotalReporter($this->token, $this->project);

        $this->assertNotFalse($reporter);

        // testing issue creation

        $issueTitle = $faker->text(100);
        $issueBody  = $faker->text(400);

        $issue = $reporter->createIssue($issueTitle, $issueBody);

        $this->assertNotFalse($issue);

        $this->assertNotEmpty($issue['name']);

        $this->assertEquals($issue['name'], $issueTitle);

        $issueId = $issue['id'];

        // testing issues loading

        $issues = $reporter->getIssues();

        $this->assertNotEmpty($issues);

        // looking for newly created issue

        $found = false;
        foreach ($issues as $issue) {
            if ($issue == $issueId) $found = true;
        }

        $this->assertTrue($found);

        // checking status

        $issue = $reporter->getSummary(null, $issueId);

        $this->assertNotEmpty($issue);

        $this->assertEquals($issue['current_state'], 'unscheduled');

        // check original content

        $this->assertEquals($issue['name'], $issueTitle);

        $description = $reporter->getDescription(null, $issueId);

        $this->assertEquals($description, $issueBody);

        // updating issue

        $newBody = $faker->text(500);

        $reporter->editIssue(null, $issueId, $newBody);

        $issue = $reporter->getSummary(null, $issueId);

        $this->assertEquals($issue['description'], $newBody);

        $this->assertNotEquals($issue['description'], $issueBody);

    }

}