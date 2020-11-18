<?php

/**
 * This file contains the "SendQuestionnaireSubmittedEmailJob" class.
 *
 * @category SilverStripe_Project
 * @package SDLT
 * @author  Catalyst I.T. SilverStripe Team 2018 <silverstripedev@catalyst.net.nz>
 * @copyright NZ Transport Agency
 * @license BSD-3
 * @link https://www.catalyst.net.nz
 */

namespace NZTA\SDLT\Job;

use SilverStripe\Control\Email\Email;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Symbiote\QueuedJobs\Services\QueuedJob;
use NZTA\SDLT\Model\QuestionnaireEmail;
use NZTA\SDLT\Model\QuestionnaireSubmission;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\Security\Member;

/**
 * A QueuedJob is specifically designed to be invoked from an onAfterWrite() process
 */
class SendQuestionnaireSubmittedEmailJob extends AbstractQueuedJob implements QueuedJob
{
    /**
     * @param QuestionnaireSubmission $questionnaireSubmission $questionnaireSubmission
     * @param  ManyManyList            $members                 A list of {@link Member} records.
     * @return void
     */
    public function __construct($questionnaireSubmission = null, $members = null)
    {
        $this->questionnaireSubmission = $questionnaireSubmission;
        $this->members = $members;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return sprintf(
            'Initialising questionnaire submitted email job for %s (%d)',
            $this->questionnaireSubmission->Questionnaire()->Name,
            $this->questionnaireSubmission->ID
        );
    }

    /**
     * {@inheritDoc}
     * @return string
     */
    public function getJobType()
    {
        return QueuedJob::QUEUED;
    }

    /**
     * @return mixed void | null
     */
    public function process()
    {
        if ($this->members) {
            foreach ($this->members as $member) {
                $this->sendEmail($member->FirstName, $member->Email);
            }
        }
    }

    /**
     * Handles the meat of the CSV import process.
     *
     * @return mixed void | null
     */
    public function sendEmail($name = '', $toEmail = '')
    {
        $emailDetails = QuestionnaireEmail::get()->first();

        $sub = $this->replaceVariable($emailDetails->QuestionnaireSubmittedEmailSubject);
        $body = $this->replaceVariable($emailDetails->QuestionnaireSubmittedEmailBody);
        $from = $emailDetails->FromEmailAddress;

        $email = Email::create()
            ->setHTMLTemplate('Email\\EmailTemplate')
            ->setData([
                'Name' => $name,
                'Body' => $body,
                'EmailSignature' => $emailDetails->EmailSignature
            ])
            ->setFrom($from)
            ->setTo($toEmail)
            ->setSubject($sub);


        $email->send();

        $this->isComplete = true;
    }

    /**
     * @param string  $string          string
     * @return string
     */
    public function replaceVariable($string = '')
    {
        $questionnaireName = $this->questionnaireSubmission->Questionnaire()->Name;
        $SubmitterName = $this->questionnaireSubmission->SubmitterName;
        $SubmitterEmail = $this->questionnaireSubmission->SubmitterEmail;
        $productName = $this->questionnaireSubmission->ProductName;

        $link = $this->questionnaireSubmission->getSummaryPageLink();

        $approvalLink = '<a href="' . $link . '">this link</a>';

        $string = str_replace('{$questionnaireName}', $questionnaireName, $string);
        $string = str_replace('{$approvalLink}', $approvalLink, $string);
        $string = str_replace('{$submitterName}', $SubmitterName, $string);
        $string = str_replace('{$submitterEmail}', $SubmitterEmail, $string);
        $string = str_replace('{$productName}', $productName, $string);

        return $string;
    }
}
