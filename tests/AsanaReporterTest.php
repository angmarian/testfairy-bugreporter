<?php

class AsanaReporterTest extends PHPUnit_Framework_TestCase
{

    protected $key      = 'aSLKuz4Y.nqs9uFzL3C3mYqyM0ZlmFIn';
    protected $project  = 'DemoProject';


    public function testReporter()
    {

        $faker = Faker\Factory::create();

        $reporter = new AsanaReporter($this->key, $this->project);

        $this->assertNotFalse($reporter);


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
            if ($issue == $issueId) $found = true;
        }

        $this->assertTrue($found);


        // checking status

        $issue = $reporter->getSummary(null, $issueId);

        $this->assertNotEmpty($issue);

        $this->assertEquals($reporter->getStatus(null, $issueId), 'Incomplete');


        // check original content

        $this->assertEquals($issue['name'], $issueTitle);

        $description = $reporter->getDescription(null, $issueId);

        $this->assertEquals($description, $issueBody);


        // updating issue

        $newBody = $faker->text(500);

        $reporter->editIssue(null, $issueId, $newBody);

        $issue = $reporter->getSummary(null, $issueId);

        $this->assertEquals($issue['notes'], $newBody);

        $this->assertNotEquals($issue['notes'], $issueBody);

    }

}