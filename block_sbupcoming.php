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
 * Filename : block_sbupcoming
 * Author   : John Welch <jwelch@welchitconsulting.co.uk>
 * Created  : 15 May 2015
 */

require_once($CFG->dirroot . '/calendar/lib.php');

class block_sbupcoming extends block_base
{
    /**
     * Initialise the block
     */
    public function init()
    {
        $this->title = get_string('pluginname', 'block_sbupcoming');
    }

    public function specialization()
    {
        $this->title = empty($this->config->title) ? format_string(get_string('newsbupcomingblock', 'block_sbupcoming'))
                                                   : format_string($this->config->title);
    }

    /**
     * Return the content of this block
     *
     * @global array $CFG The site configuration from the config file
     * @return stdClass The blocks content
     */
    public function get_content()
    {
        global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }
        $this->content = new stdClass();
        $this->content->text = '';

        $filtercourse = array();
        if (empty($this->instance)) {
            $courseshown = false;
            $this->content->footer = '';
        } else {
            $courseshown = $this->page->course->id;
            $this->content->footer = '<div class="gotocal"><a href="'
                                   . $CFG->wwwroot
                                   . '/calendar/view.php?view=upcoming&amp;course='
                                   . $courseshown
                                   . '">'
                                   . get_string('gotocalendar', 'calendar')
                                   . '</a>...</div>';
            $context = context_course::instance($courseshown);
            if (has_any_capability(array('moodle/calendar:manageentries', 'moodle/calendar:manageownentries'), $context)) {
                $this->content->footer .= '<div class="newevent"><a href="'
                                        . $CFG->wwwroot
                                        . '/calendar/event.php?action=new&amp;course='
                                        . $courseshown
                                        . '">'
                                        . get_string('newevent', 'calendar')
                                        . '</a>...</div>';
            }
            if ($courseshown == SITEID) {
                $filtercourse = calendar_get_default_courses();
            } else {
                $filtercourse = array($courseshown => $this->page->course);
            }
        }
        list($courses, $group, $user) = calendar_set_filters($filtercourse);
        $defaultlookahead = CALENDAR_DEFAULT_UPCOMING_LOOKAHEAD;
        if (isset($CFG->calendar_lookahead)) {
            $defaultlookahead = intval($CFG->calendar_lookahead);
        }
        $lookahead = get_user_preferences('calendar_lookahead', $defaultlookahead);

        $defaultmaxevents = CALENDAR_DEFAULT_UPCOMING_MAXEVENTS;
        if (isset($CFG->calendar_maxevents)) {
            $defaultmaxevents = intval($CFG->calendar_maxevents);
        }
        $maxevents = get_user_preferences('calendar_maxevents', $defaultmaxevents);
        $events = calendar_get_upcoming($courses, $group, $user, $lookahead, $maxevents);

        if (!empty($this->instance)) {
            $link = 'view.php?view=day&amp;course=' . $courseshown . '&amp;';
            $showcourselink = ($this->page->course->id == SITEID);

            $this->content->text = $this->upcoming_content($courses, $events, $link, $showcourselink);
        }

        if (empty($this->content->text)) {
            $this->content->text = '<div class="post">'
                                 . get_string('noupcomingevents', 'calendar')
                                 . '</div>';
        }
        return $this->content;
    }

    private function upcoming_content($courses, $events, $linkhref = NULL, $showcourselink = false)
    {
        $content = '';
        $lines = count($events);
        if (!$lines) {
            return $content;
        }
        for ($i = 0; $i < $lines; ++$i) {
            if (!isset($events[$i]->time)) {
                continue;
            }
            $events[$i] = calendar_add_event_metadata($events[$i]);
            $content .= '<div class="event"><span class="icon c0">'
                      . $events[$i]->icon
                      . '</span>';
            if (!empty($events[$i]->referer)) {
                $content .= $events[$i]->referer;
            } else {
                if (!empty($linkhref)) {
                    $href = calendar_get_link_href(new moodle_url(CALENDAR_URL . $linkhref), 0, 0, 0, $events[$i]->timestart);
                    $href->set_anchor('event', $events[$i]->id);
                    $content .= html_writer::link($href, $events[$i]->name);
                } else {
                    $content .= $events[$i]->name;
                }
            }
            if ($showcourselink && !empty($events[$i]->courselink)) {
                $content .= html_writer::div($events[$i]->courselink, 'course');
            }

            // Format the time string displayed
            $content .= html_writer::start_div('date');

            $endtime =  $events[$i]->timestart + $events[$i]->timeduration;
            $now = time();
            $linkparams = array('view' => 'day');
            if (!empty($courses)) {
                $courses = array_diff($courses, array(SITEID));
                if (count($courses) == 1) {
                    $linkparams['course'] = reset($courses);
                }
            }
            if ($events[$i]->timeduration) {
                $userMidnightStart = usergetmidnight($events[$i]->timestart);
                $userMidnightEnd   = usergetmidnight($endtime);

                $isoStartDate = date_format_string($events[$i]->timestart, '%FT%T');
                $isoEndDate   = date_format_string($endtime, '%FT%T');

                if ($userMidnightStart == $userMidnightEnd) {
                    if ($events[$i]->timeduration == DAYSECS) { // All day event
                        $time = get_string('allday', 'calendar');
                    } else {    // Single day event
                        $datestart = calendar_time_representation($events[$i]->timestart);
                        $dateend   = calendar_time_representation($endtime);
                        $time = html_writer::start_tag('time', array('class'    => 'upcoming-start',
                                                                     'datetime' => $isoStartDate))
                              . $datestart
                              . html_writer::end_tag('time')
                              . ' <strong>'
                              . get_string('timeto', 'block_sbupcoming')
                              . '</strong> '
                              . html_writer::start_tag('time', array('class'    => 'upcoming-end',
                                                                     'datetime' => $isoEndDate))
                              . $dateend
                              . html_writer::end_tag('time');
                    }
                    $day = calendar_day_representation($events[$i]->timestart, $now, true);
                    $url = calendar_get_link_href(new moodle_url(CALENDAR_URL . 'view.php', $linkparams), 0, 0, 0, $endtime);
                    $content .= html_writer::start_tag('time', array('class'    => 'upcoming-start',
                                                                     'datetime' => date_format_string($events[$i]->timestart, '%FT%T')))
                              . html_writer::link($url, $day)
                              . ', '
                              . trim($time);
                } else {    // Events spanning multiple days
                    $daystart = calendar_day_representation($events[$i]->timestart, $now, true) . ', ';
                    $timestart = calendar_time_representation($events[$i]->timestart);
                    $dayend = calendar_day_representation($endtime, $now, true) . ', ';
                    $timeend = calendar_time_representation($endtime);
                    if (($now >= $userMidnightStart) && ($now < strtotime('+1 day', $userMidnightStart))) {
                        $url = calendar_get_link_href(new moodle_url(CALENDAR_URL . 'view.php', $linkparams), 0, 0, 0, $endtime);
                        $content .= html_writer::start_tag('time', array('class'    => 'upcoming-start',
                                                                         'datetime' => date_format_string($events[$i]->timestart, '%FT%T')))
                                  . html_writer::link($url, $dayend)
                                  . trim($timeend)
                                  . html_writer::end_tag('time');
                    } else {
                        $url = calendar_get_link_href(new moodle_url(CALENDAR_URL . 'view.php', $linkparams), 0, 0, 0, $endtime);
                        $content .= html_writer::start_tag('time', array('class'    => 'upcoming-start',
                                                                         'datetime' => date_format_string($events[$i]->timestart, '%FT%T')))
                                  . html_writer::link($url, $daystart)
                                  . trim($timestart)
                                  . html_writer::end_tag('time')
                                  . ' <strong>'
                                  . get_string('timeuntil', 'block_sbupcoming')
                                  . '</strong> ';
                        $url = calendar_get_link_href(new moodle_url(CALENDAR_URL . 'view.php', $linkparams), 0, 0, 0, $events[$i]->timestart);
                        $content .= html_writer::start_tag('time', array('class'    => 'upcoming-start',
                                                                         'datetime' => date_format_string($events[$i]->timestart, '%FT%T')))
                                  . html_writer::link($url, $dayend)
                                  . trim($timeend)
                                  . html_writer::end_tag('time');
                    }
                }
            } else {    // No time duration
                $time = calendar_time_representation($events[$i]->timestart);
                $day = calendar_day_representation($events[$i]->timestart, $now, true);
                $url = calendar_get_link_href(new moodle_url(CALENDAR_URL . 'view.php', $linkparams), 0, 0, 0, $events[$i]->timestart);
                $content .= html_writer::start_tag('time', array('class'    => 'upcoming-start',
                                                                 'datetime' => date_format_string($events[$i]->timestart, '%FT%T')))
                          . html_writer::link($url, $day)
                          . ', '
                          . trim($time)
                          . html_writer::end_tag('time');

            }
            $content .= html_writer::end_div()
                      . html_writer::end_div();
            if ($i < $lines - 1) {
                $content .= html_writer::empty_tag('hr');
            }
        }
        return $content;
    }
}