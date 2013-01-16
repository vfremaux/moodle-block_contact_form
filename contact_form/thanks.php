<?php
require_once('../../config.php');

if (! $site = get_site()) {
    redirect($CFG->wwwroot .'/'. $CFG->admin .'/index.php');
}

print_header(strip_tags($site->fullname), $site->fullname, 
                get_string('thankyou', 'block_contact_form'), '', 
                '<meta name="description" content="'. s(strip_tags($site->summary)). '">',
                true, '', '');

$fromcourse = optional_param('fromcourse', 0, PARAM_INT);
if (!is_numeric($fromcourse) || $fromcourse == SITEID) {
    $continuepage = $CFG->wwwroot;
} else {
    $continuepage = $CFG->wwwroot . '/course/view.php?&amp;id='. $fromcourse;
}
?>

<br/>
<?php 
print_simple_box(get_string('thankyoumessage', 'block_contact_form'), 'center', '70%'); 
?>
<center>
<table>
    <tr>
        <td width="400" valign="top" align="center">
            <a target="_top" href="<?php echo $continuepage;?>"><?php print_string('continue', 'block_contact_form') ?></a>
        </td>
    </tr>
</table>
</center>
<?php print_footer(); ?>
