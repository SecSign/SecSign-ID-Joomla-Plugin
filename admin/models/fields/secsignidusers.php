<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.form.formfield');

class JFormFieldSecsignidusers extends JFormField {

    protected $type = 'Secsignidusers';

    public function getInput() {

        $html = '<select id="'.$this->id.'" name="'.$this->name.'">';

        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        $query
            ->select(array('a.id', 'a.username', 'b.secsignid', 'c.deactivate_password_login'))
            ->from($db->quoteName('#__users', 'a'))
            ->join('LEFT', $db->quoteName('#__secsignid_login', 'b') . ' ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('b.joomla_user_id') . ')')
            ->join('LEFT', $db->quoteName('#__secsignid_pw', 'c') . ' ON (' . $db->quoteName('a.id') . ' = ' . $db->quoteName('c.joomla_user_id') . ')')
            ->order($db->quoteName('a.id') . ' ASC');
        $db->setQuery($query);
        $rows = $db->loadRowList();
        $secsignidlist = array();
        $joomlaid = 0;

        //prepare array with multiple ids per user
        foreach ($rows as $row) {
            if($joomlaid == $row[0]){
                $combined = array_pop($secsignidlist);
                $combined[2] = $combined[2].', '.$row[2];
                array_push($secsignidlist,$combined);
            } else{
                $joomlaid = $row[0];
                array_push($secsignidlist,$row);
            }
        }

        //sort array asc username
        foreach ($secsignidlist as $key => $row) {
            $names[$key]  = $row[1];
        }
        array_multisort($names, SORT_ASC, $secsignidlist);
        
        $output ='<table><thead>
                <tr>
                    <td>ID</td>
                    <td>User name</td>
                    <td>SecSign ID</td>
                    <td>Deactivate password</td>
                </tr></thead><tbody><tr>
                    <td colspan="4"></td>
                </tr>';
        foreach ($secsignidlist as $row) {
            $output .= '
                    <tr>
                        <td class="secsign_bg_id">'.$row[0].'</td>
                        <td class="secsign_bg_user">'.$row[1].'<input type="hidden" value="'.$row[1].'" name="username['.$row[0].']"></td>
                        <td class="secsign_bg_sec"><input type="text" name="secsignid['.$row[0].']" id="jform_secsign_backend_servicename" value="'.$row[2].'" aria-invalid="false"></td>
                        <td class="secsign_bg_pw">';

            if($row[3])
                $output .= '<input type="checkbox" name="deactivatepassword[]" checked="checked" value="'.$row[0].'">';
            else
                $output .= '<input type="checkbox" name="deactivatepassword[]" value="'.$row[0].'">';

            $output .= '</td>
                    </tr>
                    ';
        }
        $output .="</tbody></table>";

        return $output;
    }
}