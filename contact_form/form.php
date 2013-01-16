<?php
    /**
    * Moodle - Modular Object-Oriented Dynamic Learning Environment
    *          http://moodle.org
    * Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    *
    * This program is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 2 of the License, or
    * (at your option) any later version.
    *
    * This program is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * along with this program.  If not, see <http://www.gnu.org/licenses/>.
    *
    * This file allows users to view a list of people who are
    * currently enrolled in the current course (if courseid == 1
    * then all people are shown)  This also shows groups as specified
    * in the course variables. This allows students to share a link
    * or group of links or a folder/category? to an individual, 
    * group, all students, or any combination.
    *
    * @package block-contact_form
    * @category block
    * @author Valery Fremaux (valery.fremaux@club-internet.fr)
    * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
    * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
    */
    
    /**
    * Requires and includes
    */
    require_once('../../config.php');
    require_once($CFG->dirroot.'/lib/blocklib.php');
    require_once($CFG->dirroot.'/blocks/contact_form/locallib.php');

    // this standard custom lib process might be proposed as a standard core function
    // in a recursive local override search
    if (file_exists($CFG->libdir.'/mailtemplatelib.php')){
        include_once($CFG->libdir.'/mailtemplatelib.php');
    } elseif (file_exists($CFG->dirroot.'/local/lib/mailtemplatelib.php')) {
        include_once($CFG->dirroot.'/local/lib/mailtemplatelib.php');
    } elseif (file_exists($CFG->dirroot.'/local/local/lib/mailtemplatelib.php')) {
        include_once($CFG->dirroot.'/local/local/lib/mailtemplatelib.php');
    } else {
        require_once($CFG->dirroot.'/blocks/contact_form/mailtemplatelib.php');
    }
    
    if (! $site = get_site()) {
        redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
    }

/// get parameters

    $name = optional_param('name', '', PARAM_CLEAN);
    $email = optional_param('email', '', PARAM_CLEAN);
    $forcesend = optional_param('forcesend', '', PARAM_CLEAN);
    $captcha = optional_param('captcha', '', PARAM_INT);
    
    if (!isguest()  && ($USER->emailstop || (!$USER->maildisplay && !$forcesend))){
        $emailstr = get_string('hiddenemail', 'block_contact_form');
        $email = $CFG->noreplyaddress;
    }
    else{
        $emailstr = $email;
    }
    
    $id = optional_param('id', '', PARAM_INT);
    if (!$instance = get_record('block_instance', 'id', $id)){
        print_error('badblockinstance', 'block_contact_form');
    }

    $theBlock = block_instance('contact_form', $instance);
    $comments = optional_param('comments', '', PARAM_TEXT);
    $fromcourse = optional_param('fromcourse', 0, PARAM_INT);
    $subject = optional_param('subject', null, PARAM_TEXT);
    
    /// defaults when subject is blank or first loading
    $subject = ($subject === null) ? $theBlock->config->subject : $subject ;
    
    $navlinks[] = array('name' => get_string('blockname', 'block_contact_form'), 'link' => '', 'type' => 'title');
    $navigation = build_navigation($navlinks);
    
    // start answer production
    print_header(strip_tags($site->fullname), 
                $site->fullname, 
                $navigation, 
                '', 
                '<meta name="description" content="'. s(strip_tags($site->summary)) .'">', 
                true, 
                '', 
                '');
    
    if(!is_numeric($fromcourse) || $fromcourse == 0){
        $fromcourse = SITEID;
        $messagePreface = get_string('commentreceived', 'block_contact_form') .' '. get_string('site', 'block_contact_form') .': '. $CFG->wwwroot;
    } else {
        if (! $course = get_record('course', 'id', $fromcourse) ) {
            error('That\'s an invalid course id: '. $fromcourse);
        }
        $messagePreface = get_string('commentreceived', 'block_contact_form') .' '. get_string('course', 'block_contact_form') .': '. $CFG->wwwroot . '/course/view.php?&id='. $fromcourse;
    }
    
    if ($comments != '') {
        //data was submitted from this form, process it

        $allowed = true;
        if ($theBlock->config->enablecaptcha){
            if (empty($captcha) || $captcha != $SESSION->contact_form[$id]->captcha->checkchar){
                $allowed = false;
                $err = get_string('captchaerror', 'block_contact_form');
            }
        }
        
        if ($allowed){
            $from->email = $email;
            $from->firstname = $name;
            $from->lastname = '';
            $from->maildisplay = true;
                
            $recipient = get_admin(); //get the administrator account info as email recipient
                
            $infos = array( 'SITE' => $SITE->shortname,
                            'CONTACTFORM' => $theBlock->config->title,
                            'NAME' => $name, 
                            'EMAIL' => $emailstr, 
                            'MESSAGE' => wordwrap( $comments, 1024 )
            );
            $messagePreface = compile_mail_template('contact_form', $infos, 'block_contact_form');
            $messagePrefaceHtml = compile_mail_template('contact_form_html', $infos, 'block_contact_form');
                
            //now that the admin has been notified send notice to teachers
            //check to make sure the course is not the site page first
            if ($fromcourse != 0 && $fromcourse != 1) {
                    
                //set the subject to start with [shortname]
                $subject = '[' . $course->shortname . '] '. $subject;
        
                if(isset($CFG->block_contact_form_admin_cc) && $CFG->block_contact_form_admin_cc & CONTACT_FORM_ONLY_ADMINS){
                    //we're in a course and admin should get CC
                    /// Check for error condition the hard way. Workaround for a bug in moodle discovered by Dan Marsden. If email is not configured properly and email_to_user() is called then "ERROR:" with no message prints out.
                    ob_start();
                    @email_to_user($recipient, $from, stripslashes_safe($subject), stripslashes_safe($comments));
                    $error = ob_get_contents();
                    ob_end_clean();
                    if ($CFG->debug && preg_match("/^ERROR:/", $error) ) {
                        print 'An error was encountered trying to send email in comments.php. It is likely that your email settings are not configured properly. The error reported was "'. $error .'"<br />';
                    }                
                }
                //Get teachers
                if(isset($CFG->block_contact_form_admin_cc) && $CFG->block_contact_form_admin_cc & CONTACT_FORM_ONLY_TEACHERS){
                    $coursecontext = get_context_instance(CONTEXT_COURSE, $fromcourse);
                    $teachers = get_users_by_capability($coursecontext, 'mod/course:manageactivities', 'u.id, firstname,lastname,email, picture', 'lastname');
                    if ($teachers) {
                        foreach ($teachers as $teacher) {
                            $recipient = $teacher;
                            /// Check for error condition the hard way. Workaround for a bug in moodle discovered by Dan Marsden. If email is not configured properly and email_to_user() is called then "ERROR:" with no message prints out.
                            ob_start();
                            @email_to_user($recipient, $from, stripslashes_safe($subject), stripslashes_safe($comments));
                            $error = ob_get_contents();
                            ob_end_clean();
                            if ($CFG->debug && preg_match("/^ERROR:/", $error) ) {
                                print 'An error was encountered trying to send email in comments.php. It is likely that your email settings are not configured properly. The error reported was "'. $error .'"<br />';
                            }
                        }
                    }
                }
            } else {
                if (!isset($CFG->block_contact_form_subject_prefix)) {
                    if ($site = get_site()) {
                        $CFG->block_contact_form_subject_prefix = '['. strip_tags($site->shortname) .']';
                    } else {
                        $CFG->block_contact_form_subject_prefix = '[moodle contact]';
                    }
                } 
                $subject = $CFG->block_contact_form_subject_prefix . ' ' . $subject;
                //we're not referenced from a course - just email the admin
                /// Check for error condition the hard way. Workaround for a bug in moodle discovered by Dan Marsden. If email is not configured properly and email_to_user() is called then "ERROR:" with no message prints out.
                if(isset($CFG->block_contact_form_admin_cc) && $CFG->block_contact_form_admin_cc & CONTACT_FORM_ONLY_ADMINS){
                    ob_start();
                    $adminsubject = $subject . ' '. get_string('admincopy', 'block_contact_form');
                    @email_to_user($recipient, $from, stripslashes_safe($adminsubject), stripslashes_safe($comments));
                    $error = ob_get_contents();
                    ob_end_clean();
                    if ($CFG->debug && preg_match("/^ERROR:/", $error) ) {
                        print 'An error was encountered trying to send email in comments.php. It is likely that your email settings are not configured properly. The error reported was "'. $error .'"<br />';
                    }
                    add_to_log($fromcourse, 'contact_form', 'send mail', '', "To:{$recipient->email}; From:{$from->email}; Subject:$subject");
                }
            }
        
            // notify additional users (global scope)
            if (!empty($CFG->block_contact_form_additional_cc)){
                $additional = get_record('user', 'id', $CFG->block_contact_form_additional_cc);
                if (validate_email($additional->email)){
                    ob_start();
                    @email_to_user($additional, $from, stripslashes_safe($subject), stripslashes_safe($comments));
                    $error = ob_get_contents();
                    ob_end_clean();
                    if ($CFG->debug && preg_match("/^ERROR:/", $error) ) {
                        print 'An error was encountered trying to send email in comments.php. It is likely that your email settings are not configured properly. The error reported was "'. $error .'"<br />';
                    }                
                }
            }
            // notify additional users (instance scope)
            if (!empty($theBlock->config->instance_cc) && !empty($CFG->block_contact_form_allowinstance_cc)){
                $additional = get_record('user', 'id', $theBlock->config->instance_cc);
                if (validate_email($additional->email)){
                    ob_start();
                    @email_to_user($additional, $from, stripslashes_safe($subject), stripslashes_safe($comments));
                    $error = ob_get_contents();
                    ob_end_clean();
                    if ($CFG->debug && preg_match("/^ERROR:/", $error) ) {
                        print 'An error was encountered trying to send email in comments.php. It is likely that your email settings are not configured properly. The error reported was "'. $error .'"<br />';
                    }                
                }
            }
            //Once the data is entered, redirect the user to give them visual confirmation
            redirect("{$CFG->wwwroot}/blocks/contact_form/thanks.php?fromcourse={$fromcourse}&amp;id={$id}");
        }
    }
    
    if(isset($err)){
        notify($err, 'red');
    }
    
    include('form.html'); //include the form html template file
    
    print_footer(); 
?>