<?php


/**
 * Capability definitions for onedriveforbusiness repository
 *
 * @package    repository_onedriveforbusiness
 * @copyright  2014 Werner Urech
 * @author     Werner Urech <info ät itsu dot ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$capabilities = array(
    'repository/onedriveforbusiness:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'user' => CAP_ALLOW
        )
    )
);
