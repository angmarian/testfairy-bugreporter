<?php

class YoutrackReporterTest extends PHPUnit_Framework_TestCase
{

    protected $project  = 'Test project 1';

    // protected $url      = 'http://localhost:1234';
    // protected $username = 'root';
    // protected $password = '123456';

    protected $url      = 'https://angmarian.myjetbrains.com/youtrack';
    protected $username = 'root';
    protected $password = '123456';

    public function testReporter()
    {

        $faker = Faker\Factory::create();

        $reporter = new YoutrackReporter($this->url, $this->username, $this->password, $this->project);

        $this->assertNotFalse($reporter);


        // testing issue creation

        $issueTitle = $faker->text(100);
        $issueBody  = $faker->text(400);

        $issue = $reporter->createIssue($issueTitle, $issueBody);

        $this->assertNotFalse($issue);

        $this->assertNotEmpty($issue['summary']);

        $this->assertEquals($issue['summary'], $issueTitle);

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

        $this->assertEquals($reporter->getStatus(null, $issueId), 'Submitted');


        // check original content

        $this->assertEquals($issue['summary'], $issueTitle);

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