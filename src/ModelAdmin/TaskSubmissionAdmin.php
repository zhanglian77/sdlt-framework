<?php

/**
 * This file contains the "TaskSubmissionAdmin" class.
 *
 * @category SilverStripe_Project
 * @package SDLT
 * @author  Catalyst I.T. SilverStripe Team 2018 <silverstripedev@catalyst.net.nz>
 * @copyright 2018 Catalyst.Net Ltd
 * @license https://www.catalyst.net.nz (Commercial)
 * @link https://www.catalyst.net.nz
 */

namespace NZTA\SDLT\ModelAdmin;

use NZTA\SDLT\Model\TaskSubmission;
use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldViewButton;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\Tab;

/**
 * Class TaskSubmissionAdmin
 *
 * This class is used to manage Questionnaires sumission
 */
class TaskSubmissionAdmin extends ModelAdmin
{
    /**
     * @var string[]
     */
    private static $managed_models = [
        TaskSubmission::class,
    ];

    /**
     * @var string
     */
    private static $url_segment = 'task-submission-admin';

    /**
     * @var string
     */
    private static $menu_title = 'Task Submissions';

    /**
     * @param int       $id     ID
     * @param FieldList $fields Fields
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);

        $gridFieldName = $this->sanitiseClassName($this->modelClass);

        /* @var GridField $gridField */
        $currentGridField = $form->Fields()->fieldByName($gridFieldName);
        $list = $currentGridField->getList();
        $currentList = $list->exclude('Status', 'expired');
        $currentGridField->setList($currentList);
        $config = GridFieldConfig_RelationEditor::create();
        $config->removeComponentsByType(GridFieldAddNewButton::class);

        //$config->removeComponentsByType(GridFieldEditButton::class);
        $config->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
        $config->AddComponents(new GridFieldViewButton());
        $currentGridField->setConfig($config);

        $expiredSubmission = $list->filter('Status', TaskSubmission::STATUS_EXPIRED);

        $expiredGridField = new GridField(
            'ExpiredSubmissions',
            ' ',
            $expiredSubmission,
            $config
        );

        $fields = new FieldList(
            $root = new TabSet(
                'Root',
                new Tab(
                    'CurrentSubmissions',
                    'Current' . '(' . count($currentList) . ')',
                    $currentGridField
                ),
                new Tab(
                    'ExpiredSubmissions',
                    'Expired' . '(' . count($expiredSubmission) . ')',
                    $expiredGridField
                    )
                )
            );

        $form->setFields($fields);

        return $form;
    }
}
