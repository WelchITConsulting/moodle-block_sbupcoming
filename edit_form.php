<?php
/*
 * Copyright (C) 2015 Welch IT Consulting
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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
 * Filename : edit_form
 * Author   : John Welch <jwelch@welchitconsulting.co.uk>
 * Created  : 16 May 2015
 */

class block_sbupcoming_edit_form extends block_edit_form
{
    protected function specific_definition($mform)
    {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_sbupcoming'));
        $mform->setType('config_title', PARAM_TEXT);
    }

    function set_data($defaults)
    {
        parent::set_data($defaults);
    }
}
