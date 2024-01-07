<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 *
 * @package   report_trainingenrolment
 * @category  Report Plugin
 * @copyright 2018 Sandeep Gill {support@lingellearning.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_trainingenrolment\output;


use renderer_base;
use report_trainingenrolment\helper\adhoc_report_data_helper;
use report_trainingenrolment\helper\data_helper;
use stdClass;

class postcode_report implements \renderable, \templatable
{

    /**
     * Prepares the data for Postcode report export
     *
     * @param renderer_base $output
     * @return array|stdClass
     * @throws \moodle_exception
     */
    public function export_for_template(renderer_base $output)
    {
        $data = adhoc_report_data_helper::get_data_by_postcodes_with_state_mapping();

//        $template_data = [
//            'states' => [
//                [
//                    'label'     => 'ACT',
//                    'shortname' => 'act',
//                    'first'     => true,
//                    'postcodes' => [
//                        'postcode' => 1106,
//                        'count'    => 10
//                    ]
//                ],
//                [
//                    'label'     => 'NSW',
//                    'shortname' => 'nsw',
//                    'postcodes' => [
//                        'postcode' => 1106,
//                        'count'    => 10
//                    ]
//                ],
//                [
//                    'label'     => 'NT',
//                    'shortname' => 'nt',
//                    'postcodes' => [
//                        'postcode' => 1106,
//                        'count'    => 10
//                    ]
//                ],
//            ]
//        ];

        $template_data             = new stdClass();
        $template_data->export_url = new \moodle_url('/report/trainingenrolment/postcodes.php', ['download' => 1]);
        $template_data->states     = [];

        $count = 0;
        foreach ($data as $state_label => $state_data) {
            $state            = new stdClass();
            $state->label     = $state_label;
            $state->shortname = strtolower($state_label);
            $state->postcodes = [];
            $state->first     = $count == 0;

            foreach ($state_data as $postcode_text => $user_type_data) {
                $postcode                                                 = new stdClass();
                $postcode->postcode                                       = $postcode_text;
                $postcode->{'count_admin'}                                = !empty($user_type_data['admin']) ? $user_type_data['admin'] : '';
                $postcode->{'count' . data_helper::USER_TYPE_NO_AHPRA_ID} = !empty($user_type_data[data_helper::USER_TYPE_NO_AHPRA_ID]) ? $user_type_data[data_helper::USER_TYPE_NO_AHPRA_ID] : '';
                $postcode->{'count' . data_helper::USER_TYPE_MED}         = !empty($user_type_data[data_helper::USER_TYPE_MED]) ? $user_type_data[data_helper::USER_TYPE_MED] : '';
                $postcode->{'count' . data_helper::USER_TYPE_NUR}         = !empty($user_type_data[data_helper::USER_TYPE_NUR]) ? $user_type_data[data_helper::USER_TYPE_NUR] : '';
                $postcode->{'count' . data_helper::USER_TYPE_PAR}         = !empty($user_type_data[data_helper::USER_TYPE_PAR]) ? $user_type_data[data_helper::USER_TYPE_PAR] : '';
                $postcode->{'count' . data_helper::USER_TYPE_PHA}         = !empty($user_type_data[data_helper::USER_TYPE_PHA]) ? $user_type_data[data_helper::USER_TYPE_PHA] : '';
                $postcode->{'count' . data_helper::USER_TYPE_ATSI}        = !empty($user_type_data[data_helper::USER_TYPE_ATSI]) ? $user_type_data[data_helper::USER_TYPE_ATSI] : '';
                $state->postcodes[]                                       = $postcode;
            }

            $template_data->states[] = $state;
            $count++;
        }

        return $template_data;
    }
}
