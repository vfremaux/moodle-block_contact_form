<?php //$Id: block_contact_form.php,v 1.3 2010/01/29 14:07:48 vf Exp $

class block_contact_form extends block_base {

    function init() {
        //title will be 'Ask a question' in the blocks admin block's drop down menu and on blocks admin page
        $this->title = get_string('askaquestion', 'block_contact_form');
        $this->content_type = BLOCK_TYPE_TEXT;
        $this->version = 2007091700;
    }

    function applicable_formats() {
        return array('course' => true, 'site' => true, 'learning' => false);
    }

    function specialization() {
        // set the block title
        if (!empty($this->config) && !empty($this->config->title) ) {
            $this->title = format_string($this->config->title);
        } else {
            if (!empty($this->instance) && $this->instance->pagetype == PAGE_COURSE_VIEW && $this->instance->pageid == SITEID) {
                //we're displaying on the site page
                $this->title = get_string('contactus', 'block_contact_form');
            } else {
                // we're in a course or on a blog
                $this->title = get_string('askaquestion', 'block_contact_form');
            }
        }
    }

    function get_content() {
        global $USER, $CFG;

        if($this->content !== NULL) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->text = ''; //empty to start, will be populated below
        $this->content->header = $this->title;
        
        if (empty($this->instance)) {
            // We're being asked for content without an associated instance
            return $this->content;
        }

        $fromcourse = $this->instance->pageid;
        
        //init subject and displaytype to defaults
        if (isset($CFG->block_contact_form_display_type)) {
            $displaytype = $CFG->block_contact_form_display_type;
        } else {
            $displaytype = 0;
        }
        $subject = '';
        $linktext = $this->title;

        //set subject, linktext and displaytype to stored values if present
        if (!empty($this->config)) {
            if (!empty($this->config->displaytype)) {
                $displaytype = $this->config->displaytype;
            }
            if (!empty($this->config->subject)) {
                $subject = format_string($this->config->subject);
            }
            if (!empty($this->config->linktext)) {
                $linktext = format_string($this->config->linktext);
            }
        }

        
        //check our configuration setting to see what format we should display
        // 0 == display a form button
        // 1 == display a link
        if ($displaytype == 1){
            $this->content->text = '<center>';
            $this->content->text .= "<a href=\"{$CFG->wwwroot}/blocks/contact_form/form.php?fromcourse={$fromcourse}&amp;id={$this->instance->id}&amp;subject=$subject\">";
            $this->content->text .=  $linktext;
            $this->content->text .=  '</a>';
            $this->content->text .= '</center>';
        } else {
            $this->content->text = '<center>';
            $this->content->text .= '<form name="form" method="post" action="'. $CFG->wwwroot .'/blocks/contact_form/form.php">';
            $this->content->text .= '<table align="center" border="0" cellspacing="0">';
            $this->content->text .= '<tr><td valign="top" align="center"><input type="hidden" name="fromcourse" value="'. $fromcourse .'" />';
            $this->content->text .= '<input type="submit" name="Submit" value="'. $linktext .'" />';
            $this->content->text .= '<input type="hidden" name="id" value="'. $this->instance->id .'" />';
            $this->content->text .= '<input type="hidden" name="subject" value="'. $subject .'" />';
            $this->content->text .= '</td></tr></table></form>';
            $this->content->text .= '</center>';
        }
        
        return $this->content;
    }

    function instance_allow_multiple() {
        return true;
    }
    
    function has_config() {
        return true;
    }
    
    /**
    * do we have local config
    */
    function instance_allow_config() {
        global $COURSE;

        // if admin always configure
        if (isadmin()) return true;

        // if not "MyMoodle" and is teacher, can configure
        if (isteacher($COURSE->id)){
            if ($COURSE->id > 1)
                return true;
        }
        return false;
    }
}
?>
