<?php
/**
 * Copyright (c) 2011 Rusmin Soetjipto
 * ported to Dokuwiki by Yvonne Lu
 * generalized by Matt Glosson
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class UsfmParagraphState {
  
  private $list_level = 0;
  private $indent_level = 0;  // 0 for normal (level-1) paragraph
                              // 1 for level-1 poetry & indented
                              //   paragraph
  private $is_italic = False;
  private $paragraph_class = '';
  private $is_open = False;

   
function switchListLevel($new_list_level) {
    $result = '';
    for ($r = $new_list_level; $r < $this->list_level; $r++) {
      $result .= "\n</ul>";
    }
    for ($r = $this->list_level; $r < $new_list_level; $r++) {
      $result .= "\n<ul class='usfm'>";
    }
    $this->list_level = $new_list_level;
    return $result;
  }  
  
  
  function closeParagraph() {
    if ($this->is_open) {
      if (!isset($result))
       $result = null;

      $result .= $this->switchListLevel(0); 
      if ($this->is_italic) {
        $result .= '</i>';
        $this->is_italic = False;
      }  
      $result .= "</p>\n";
      $this->is_open = False;
      return $result;
    } else {
      return '';
    }
  }  
  
 
  private function switchIndentLevel($new_indent_level) {
    $result = $this->closeParagraph();
    
    for ($r = $new_indent_level; $r < $this->indent_level; $r++) {
      $result .= "</blockquote>\n";
    }
    for ($r = $this->indent_level; $r < $new_indent_level; $r++) {
      $result .= "<blockquote class='usfm'>\n";
    }
    $this->indent_level = $new_indent_level;
    return $result;
  }
  
  
 function switchParagraph($new_indent_level, $is_italic, $alignment, 
                           $paragraph_class)
  {
    $result = $this->switchIndentLevel($new_indent_level);
    //$result .= "<p class='".$paragraph_class."' align='".$alignment."'>";
    # above line was commented out because html5 does not like alignment set in the p element, but in css
    $result .= "<p class='".$paragraph_class."'>";
    if ($is_italic) {
      $this->is_italic = True;
      $result .= '<i>';
    }
    $this->paragraph_class = $paragraph_class;
    $this->is_open = True;
    return $result;
  }
  
 function printTitle($is_horizontal_line, $level, $is_italic,
                      $content) {
    $result = $this->switchIndentLevel(0);

    if ($level > 6) {
      $level = 6;
    }
    if (isset($content) && !empty(trim($content))) {
     if ($is_italic) {
       $result .= "<h".$level." class='usfm'><em>".$content."</em></h".$level.">";
     } else {
       $result .= "<h".$level." class='usfm'>".$content."</h".$level.">";
     }
    }
    return $result;
  }
  
  //111
  function isItalic() {
    return $this->is_italic;
  }
  
  //115
  function isOpen() {
  	return $this->is_open;
  }
  
  //119
  function getParagraphClass() {
  	return $this->paragraph_class;
  }
}
/**
 * 1/30/14 the following functions are ported
 *  renderGeneralCommand
 *  switchListLevel
 *  setAlternateChapterNumber
 *  setPublishedChapterNumber
 *  setAlternateVerseNumber
 *  all functions from the original UsfmText.php should be ported
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * 7-25-14 Yvonne Lu
 * changed function newAnchorLabel to generate number instead of letter labels
 *
 * 8-6-14 Yvonne Lu
 * commented out some code related to is_verse_popups_extension_available
 *
 * 8-8-14 Yvonne Lu
 * generating footnote number starting at 1 instead of 0
 *
 * 1-14-15 YvonneLu
 * Added popup window for footnotes
 *
 * 3-18-15 Yvonne Lu
 * Took out link to stylesheet in getAndClearHtmlText.  It should take place
 * in the header.
 *
 */

class UsfmBodyOrFooter {
    private $html_text = '';
    private $is_verse_popups_extension_available = false;
    const BIBLE_VERSE_REFERENCE_PATTERN_1 = "/\\b([1-3]\\s)?[A-Z][a-z]+\\.?\\s\\d+([\\:\\.]\\d+)?([\\-\\~]\\d+)?";
    const BIBLE_VERSE_REFERENCE_PATTERN_2 = "([\\;\\,]\\s?\\d+([\\:\\.]\\d+)?([\\-\\~]\\d+)?)*/";

    function __construct() {
        $this->is_verse_popups_extension_available = false;
    }

    function printHtmlText($html_text) {
        //echo "&quot$html_text&quot<br>";
        $this->html_text .= $html_text;
    }

    function getAndClearHtmlText() {
        $result          = $this->html_text;
        $this->html_text = '';
        return $result;
    }
}

class UsfmText {
    private $book_name = '';
    private $is_book_started = false;
    private $latest_chapter_number = 0;

    private $chapter_label = '';
    private $chapter_number = '';
    private $alternate_chapter_number = '';
    private $published_chapter_number = '';
    private $is_current_chapter_using_label = false;

    private $verse_number = '';
    private $alternate_verse_number = '';

    private $table_data = array();
    private $is_in_table_mode = false;
    const IS_HEADER = 0;
    const IS_RIGHT_ALIGNED = 1;
    const CONTENT_TEXT = 2;

    private $paragraph_state;
    private $body;
    private $footer;
    private $is_in_footer_mode = false;

    private $anchor_count = -1;

    private $flush_paragraph_settings = array(
        "default" => "usfm-flush"
    );
    private $drop_cap_numeral_settings = array(
        "usfm-indent" => "usfm-c-indent",
        "default"     => "usfm-c"
    );
    private $pre_chapter_paragraph_classes =
        array("usfm-desc");

    const INDENT_LEVEL = 0;
    const IS_ITALIC = 1;
    const ALIGNMENT = 2;
    const PARAGRAPH_CLASS = 3;
    private $default_paragraph =
        array(0, false, 'justify', 'usfm-indent');

    //yil no parser yet
    function __construct() {

        $this->paragraph_state = new UsfmParagraphState();
        $this->body            = new UsfmBodyOrFooter(); //yil no parser for now
        $this->footer          = new UsfmBodyOrFooter(); //yil no parser for now
    }

    private function getSetting($key, $settings) {
        if(array_key_exists($key, $settings)) {
            return $settings[$key];
        }
        else {
            return $settings["default"];
        }
    }

// 10 NOV 2015, Phil Hopper, Issue #368: This is causing an error and is not currently needed on door43.org
//    function setChapterLabel($chapter_label) {
//        if(($this->chapter_number <> '') ||
//            ($this->alternate_chapter_number <> '') ||
//            ($this->published_chapter_number <> '') ||
//            ($this->is_book_started)
//        ) {
//            $this->chapter_label                  = $chapter_label;
//            $this->is_current_chapter_using_label = true;
//        }
//        else {
//            $this->setBookName($chapter_label);
//        }
//    }

    function setChapterNumber($chapter_number) {
        $this->chapter_number        = $chapter_number;
        $this->latest_chapter_number = $chapter_number;
    }

    function setAlternateChapterNumber($alternate_chapter_number) {
        $this->alternate_chapter_number = $alternate_chapter_number;
    }

    function setPublishedChapterNumber($published_chapter_number) {
        $this->published_chapter_number = $published_chapter_number;
    }

    private function getFullChapterNumber() {
        if($this->chapter_number && $this->alternate_chapter_number) {
            return $this->chapter_number . "(" .
            $this->alternate_chapter_number . ")";
        }
        elseif($this->chapter_number) {
            return $this->chapter_number;
        }
        else {
            return $this->alternate_chapter_number;
        }
    }

    private function isDropCapNumeralPending() {
        return ($this->published_chapter_number <> '') ||
        ($this->getFullChapterNumber() <> '');
    }

    function flushPendingDropCapNumeral($is_no_break) {
        $final_chapter_number = $this->published_chapter_number ?
            $this->published_chapter_number :
            $this->getFullChapterNumber();
        if($final_chapter_number) {
            $this->chapter_number           = '';
            $this->alternate_chapter_number = '';
            $this->published_chapter_number = '';
            if($is_no_break || ((!$this->book_name) &&
                    (!$this->is_current_chapter_using_label))
            ) {
                $drop_cap_numeral_class =
                    $this->getSetting(
                        $this->paragraph_state->getParagraphClass(),
                        $this->drop_cap_numeral_settings
                    );
                $this->body
                    ->printHtmlText(
                        "<span class='" . $drop_cap_numeral_class . "'>" .
/*
                        "<big class='usfm-c'><big class='usfm-c'>" .
                        "<big class='usfm-c'><big class='usfm-c'>" .
*/
                        $final_chapter_number .
                        #"</big></big></big></big></span>"
                        "</span>"
                    );
            }
        }
        $this->is_current_chapter_using_label = false;
    }

    private function flushPendingChapterLabel() {
        if($this->chapter_label) {
            $this->body
                ->printHtmlText(
                    $this->paragraph_state
                        ->printTitle(
                            false, 3, false,
                            $this->chapter_label
                        )
                );
            $this->chapter_label = '';
        }
        elseif($this->book_name) {
            $label_text = $this->book_name . " " .
                $this->getFullChapterNumber();
            $this->body->printHtmlText(
                $this->paragraph_state
                    ->printTitle(
                        false, 3, false,
                        $label_text
                    )
            );
        }
    }

    function printTitle($level, $is_italic, $content) {
        $this->body->printHtmlText(
            $this->paragraph_state
                ->closeParagraph()
        );
        $this->flushPendingChapterLabel();
        $this->body->printHtmlText(
            $this->paragraph_state
                ->printTitle(
                    false, $level,
                    $is_italic,
                    $content
                )
        );
    }

    function switchParagraph($new_indent_level, $is_italic, $alignment,
                             $paragraph_class) {
        $this->body->printHtmlText(
            $this->paragraph_state
                ->closeParagraph()
        );
        $this->flushPendingChapterLabel();
        $is_pre_chapter_paragraph =
            (false !== array_search(
                    $paragraph_class,
                    $this->pre_chapter_paragraph_classes
                ));

        /* yil commented out debug statement
        wfDebug("switchParagraph: ".($is_pre_chapter_paragraph ? "T" : "F").
                " ".$paragraph_class."\n");*/
        if((!$is_pre_chapter_paragraph) &&
            $this->isDropCapNumeralPending()
        ) {
            $paragraph_class =
                $this->getSetting(
                    $this->paragraph_state->getParagraphClass(),
                    $this->flush_paragraph_settings
                );
        }
        $this->body->printHtmlText(
            $this->paragraph_state
                ->switchParagraph(
                    $new_indent_level,
                    $is_italic,
                    $alignment,
                    $paragraph_class
                )
        );
        if(!$is_pre_chapter_paragraph) {
            $this->flushPendingDropCapNumeral(false);
        }

    }

    function setVerseNumber($verse_number) {
        $this->verse_number = $verse_number;
    }

    function setAlternateVerseNumber($alternate_verse_number) {
        $this->alternate_verse_number = $alternate_verse_number;
    }

    private function flushPendingVerseInfo() {
        if(($this->alternate_verse_number <> '') ||
            ($this->verse_number <> '')
        ) {
            if(!$this->paragraph_state->isOpen()) {
                $this->switchParagraph(
                    $this->default_paragraph[self::INDENT_LEVEL],
                    $this->default_paragraph[self::IS_ITALIC],
                    $this->default_paragraph[self::ALIGNMENT],
                    $this->default_paragraph[self::PARAGRAPH_CLASS]
                );
            }
            $anchor_verse = $this->verse_number ? $this->verse_number :
                $this->alternate_verse_number;
            if(($this->alternate_verse_number <> '') &&
                ($this->verse_number <> '')
            ) {
                $verse_label = $this->verse_number . " (" . $this->alternate_verse_number . ")";
            }
            else {
                $verse_label = $anchor_verse;
            }
            $this->body->printHtmlText(
                " <span class='usfm-v'><b class='usfm'>" .
                "<a id='" . $this->latest_chapter_number . "_" .
                $anchor_verse . "'></a>" . $verse_label .
                "</b></span>"
            );
            $this->verse_number           = '';
            $this->alternate_verse_number = '';
        }
    }

    function insertTableColumn($is_header, $is_right_aligned, $text) {
        //yil commented out debug statement
        //wfDebug("inserting table column: ".$text."\n");
        $this->table_data[] = array(
            $is_header, $is_right_aligned,
            $text
        );
    }

    function flushPendingTableColumns() {

        if(!$this->is_in_table_mode) {
            $this->is_in_table_mode = true;
            $this->body->printHtmlText("\n<table class='usfm'>");
        }
        if(count($this->table_data) > 0) {
            $this->body->printHtmlText("\n<tr class='usfm'>");
            foreach($this->table_data as $data) {
                $html_text =
                    "\n<td class='usfm-" . ($data[self::IS_HEADER] ? 'th' : 'tc') .
                    ($data[self::IS_RIGHT_ALIGNED] ? "' align='right" : "") .
                    "'>" . $data[self::CONTENT_TEXT] . "</td>\n";
                $this->body->printHtmlText($html_text);
            }
            $this->table_data = array();
        }
    }

    function printHtmlTextToBody($html_text) {
        $this->is_book_started = true;
        if($this->is_in_table_mode) {
            $this->flushPendingTableColumns();
            $this->body->printHtmlText("\n</table>\n");
            $this->is_in_table_mode = false;
        }
        $this->flushPendingVerseInfo();

        $this->body->printHtmlText($html_text);
    }

    function printItalicsToBody($if_normal, $if_italic_paragraph) {
        if($this->paragraph_state->isItalic()) {
            $this->printHtmlTextToBody($if_italic_paragraph);
        }
        else {
            $this->printHtmlTextToBody($if_normal);
        }
    }

    function printHtmlTextToFooter($html_text) {
        $this->footer->printHtmlText($html_text);
    }

    function printHtmlText($html_text) {
        if($this->is_in_footer_mode) {
            $this->printHtmlTextToBody($html_text);  //YIL added text for popup window
            $this->printHtmlTextToFooter($html_text);
        }
        else {
            $this->printHtmlTextToBody($html_text);
        }
    }

    function switchListLevel($new_list_level) {
        $this->printHtmlTextToBody(
            $this->paragraph_state
                ->switchListLevel($new_list_level)
        );
    }

    private function numToLetter($number) {
     $asciiCode = $number+96;
     if ($asciiCode < 123) {
      $letter = chr($asciiCode);
     } else {
      $firstPart = floor(($asciiCode-97)/26);
      $secondPart = ($asciiCode-97) % 26;
      $letter = numToLetter($firstPart) . '' . numToLetter($secondPart+1);
     }
     return $letter;
    }

    //1-14-15 added popup window for footnote
    function newFooterEntry() {
        $this->is_in_footer_mode = true;
        $anchor_label            = $this->newAnchorLabel();
       	$foot_letter = $this->numToLetter($anchor_label);

        $this->printHtmlTextToBody(
            '<span class="popup_marker"><span class="usfm-f1">' .
            '[<a id="' . $anchor_label . '*" href="#' . $anchor_label . '">'.$foot_letter.'</a>] ' .
            '</span><span class="popup">'
        );

        /** @noinspection HtmlUnknownAnchorTarget */
        $this->printHtmlTextToFooter(
            '<p class="usfm-footer"><span class="usfm-f2">' .
            '[<a id="' . $anchor_label . '" href="#' . $anchor_label . '*">'.$foot_letter.'</a>] ' .
            '</span>'
        );
    }

    //yil this function originally generates letter footnote labels.
    //It's changed to number so that international users can have an easier time to read it.
    private function newAnchorLabel() {
        $count        = ++$this->anchor_count;
        $anchor_label = strval(($count + 1)); //generating footnote number starting at 1 instead of 0
        /* yil original letter generating label code
        $anchor_label = '';
        do {
          $anchor_label = chr(ord('a') + ($count % 26)) . $anchor_label;
          $count = (int) floor($count / 26);
        } while ($count > 0);*/
        return $anchor_label;
    }

    function closeFooterEntry() {
        //end popup
        $this->printHtmlTextToBody("</span></span>"); //added popup window end tags

        $this->is_in_footer_mode = false;
        $this->printHtmlTextToFooter("</p>");
    }

    function getAndClearHtmlText() {

        $this->printHtmlTextToBody('');
        /*
        return "<link rel='stylesheet' href='lib".DIRECTORY_SEPARATOR."plugins".DIRECTORY_SEPARATOR.
                                          "usfmtag".DIRECTORY_SEPARATOR."style.css'".
             " type='text/css'>".
             $this->body->getAndClearHtmlText().
         $this->paragraph_state
              ->printTitle(True, 4, False, "").
         $this->footer->getAndClearHtmlText();*/

        return $this->body->getAndClearHtmlText() .
        $this->paragraph_state
            ->printTitle(true, 4, false, "") .
        $this->footer->getAndClearHtmlText();
    }

    //YIL added this function to corrent indent bug
    function closeParagraph() {
        $this->body->printHtmlText(
            $this->paragraph_state
                ->closeParagraph()
        );
    }

}

/**
 * 10/6/14 Yvonne Lu
 * Correct indent problem in poetry
 *
 * 8/13/14 Yvonne Lu
 * Implemented /ip as paragraph
 * Implemented /is and /imt as section headings
 *
 * 8/6/14 Yvonne Lu
 * translate \s5 to <hr>
 *
 * Fixed space before punctuation problem for add tags
 *
 * 7/25/14
 * Disabled formatting for \add tags <jesse@distantshores.org>
 *
 *
 * 6/28/14
 * Corrected a bug concerning command parsing.  Punctuation was parsed with the
 * command which caused invalid rendering behavior.  I've noticed that many of the
 * php string functions utilized in the original code are single byte functions.
 * This may cause a problem when the string is in unicode that requires double
 * byte operation. Also, preg_match and ereg_match both hangs my version of
 * dokuwiki.  As a result, I was not able to use these functions.
 *
 *
 * 1/30/14
 * ported function renderOther, renderTable, renderIntroduction to support command
 * 'i', 'it', 'd', 'r', 't', 'tl','x'
 *
 *
 * There seems to be a bug in function renderChapterOrVerse for setting
 * alternate verse number and chapter.  It was using an uninitialized variable,
 * verse number.  I commented out the action for now.
 *
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class UsfmTagDecoder {
    private $usfm_text;
    private $is_selah_cross_reference = false;

    const BASE_LEVEL = 0;
    const IS_ITALIC = 1;
    const ALIGNMENT = 2;
    const PARAGRAPH_CLASS = 3;

    private $paragraph_settings = array(
        // Chapter and Verses
        "cd"  => array(0, true, 'left', 'usfm-desc'),
        // Titles, Headings, and Label
        "d"   => array(0, true, 'left', 'usfm-desc'),
        "sp"  => array(0, true, 'left', 'usfm-flush'),
        // Paragraph and Poetry (w/o level parameter)
        "cls" => array(0, false, 'right', 'usfm-right'),
        "m"   => array(0, false, 'justify', 'usfm-flush'),
        "mi"  => array(1, false, 'justify', 'usfm-flush'),
        "p"   => array(0, false, 'justify', 'usfm-indent'),
        "pc"  => array(0, false, 'center', 'usfm-center'),
        "pm"  => array(1, false, 'justify', 'usfm-indent'),
        "pmc" => array(1, false, 'justify', 'usfm-flush'),
        "pmo" => array(1, false, 'justify', 'usfm-flush'),
        "pmr" => array(1, false, 'right', 'usfm-right'),
        "pr"  => array(0, false, 'right', 'usfm-right'),
        "qa"  => array(1, true, 'center', 'usfm-center'),
        "qc"  => array(1, false, 'center', 'usfm-center'),
        "qr"  => array(1, true, 'right', 'usfm-right'),
        // Paragraph and Poetry (w/ level parameter)
        "ph"  => array(0, false, 'justify', 'usfm-hanging'),
        "pi"  => array(1, false, 'justify', 'usfm-indent'),
        "q"   => array(2, false, 'left', 'usfm-hanging'),
        "qm"  => array(1, true, 'left', 'usfm-hanging'),
        "ip"  => array(0, false, 'justify', 'usfm-indent')
    );
    private $title_settings = array(
        // Titles, Headings, and Label (w/o level parameter)
        "mr"  => array(2, true),
        "r"   => array(5, true),
        "sr"  => array(5, true),
        // Titles, Headings, and Label (w/ level parameter)
        "imt" => array(1, false),
        "is"  => array(1, false),
        "mt"  => array(1, false),
        "mte" => array(1, false),
        "ms"  => array(2, false),
        "s"   => array(3, false),
    );
    const IF_NORMAL = 0;
    const IF_ITALIC_PARAGRAPH = 1;
    private $substitution_table = array(
        // Titles, Headings, and Labels
        "rq"    => array("\n<span class='usfm-selah'><i class='usfm'>"),
        "rq*"   => array("</i></span>\n"),
        // Paragraphs and Poetry
        "b"     => array("\n<br>"),
        "qac"   => array("<span style='font-size: larger;'  class='usfm-qac'>"),
        "qac*"  => array("</span>"),
        "qs"    => array("\n<span class='usfm-selah'><i class='usfm'>"),
        "qs*"   => array("</i></span>\n"),
        // Cross Reference
        "x"     => array("\n<span class='usfm-selah'>"),
        "x*"    => array("</span>\n"),
        // Other
        // 7-25-14 disabled formatting for \add tags <jesse@distantshores.org>
        //"add"  => array ("<i class='usfm'>[", "</i>["),
        //"add*" => array ("]</i>", "]<i class='usfm'>"),
        "add"   => array(" "),
        "add*"  => array(""),
        "bk"    => array("<i class='usfm'>&quot;", "</i>&quot;"),
        "bk*"   => array("&quot;</i>", "&quot;<i class='usfm'>"),
        "dc"    => array("<code class='usfm'>"),
        "dc*"   => array("</code>"),
        "k"     => array("<code class='usfm'>"),
        "k*"    => array("</code>"),
        "lit"   => array("\n<span class='usfm-selah'><b class='usfm'>"),
        "lit*"  => array("</b></span>\n"),
        "ord"   => array("<sup class='usfm'>"),
        "ord*"  => array("</sup>"),
        "pn*"   => array(""),
        "qt"    => array("<i class='usfm'>", "</i>"),
        "qt*"   => array("</i>", "<i class='usfm'>"),
        "s5"    => array("<hr>"), //Yvonne added 8/6/14
        "sig"   => array("<i class='usfm'>", "</i>"),
        "sig*"  => array("</i>", "<i class='usfm'>"),
        "sls"   => array("<i class='usfm'>", "</i>"),
        "sls*"  => array("</i>", "<i class='usfm'>"),
        "tl"    => array("<i class='usfm'>", "</i>"),
        "tl*"   => array("</i>", "<i class='usfm'>"),
        "wj"    => array("<span class='wj'>"),  //Matt changed 3/3/17
        "wj*"   => array("</span>"),
        "em"    => array("<i class='usfm'>", "</i>"),
        "em*"   => array("</i>", "<i class='usfm'>"),
        "bd"    => array("<b class='usfm'>"),
        "bd*"   => array("</b>"),
        "it"    => array("<i class='usfm'>", "</i>"),
        "it*"   => array("</i>", "<i class='usfm'>"),
        "bdit"  => array("<i class='usfm'><b class='usfm'>", "</i></b>"),
        "bdit*" => array("</b></i>", "<b class='usfm'><i class='usfm'>"),
        "no"    => array("", "</i>"),
        "no*"   => array("", "<i class='usfm'>"),
        "sc"    => array("<small class='usfm'>"),
        "sc*"   => array("</small>"),
        "\\"    => array("<br>"),
        "skip"  => array("</usfm> <br>~~NO_STYLING~~"),
        "skip*" => array("<br>~~NO_STYLING~~ <br><usfm>")
    );
    const BEFORE_REMAINING = 0;
    const AFTER_REMAINING = 1;

    private $footnote_substitution_table = array(
        // Footnotes
        "fdc"  => array("<i class='usfm'>", ""),
        "fdc*" => array("</i>", ""),
        "fl"   => array("<span style='text-decoration: underline;' class='usfm'>", "</span>"),
        "fm"   => array("<code class='usfm'>", ""),
        "fm*"  => array("</code>", ""),
        "fp"   => array("</p>\n<p class='usfm-footer'>", ""),
        "fq"   => array("<i class='usfm'>", "</i>"),
        "fqa"  => array("<i class='usfm'>", "</i>"),
        "fr"   => array("<b class='usfm'>", "</b>"),
        "fv"   => array(" <span class='usfm-v'>", "</span>"),
        // Cross References
        "xdc"  => array("<b class='usfm'>", ""),
        "xdc*" => array("</b>", ""),
        "xnt"  => array("<b class='usfm'>", ""),
        "xnt*" => array("</b>", ""),
        "xot"  => array("<b class='usfm'>", ""),
        "xot*" => array("</b>", ""),
        "xo"   => array("<b class='usfm'>", "</b>"),
        "xq"   => array("<i class='usfm'>", "</i>")
    );

    const MAX_SELAH_CROSS_REFERENCES_LENGTH = 10;

    private $is_poetry = false; //yil added this to solve indent problem

    public function __construct() {
        //yil no parser for now until I find out what it does
        $this->usfm_text = new UsfmText();
        //$this->usfm_text = new UsfmText($parser);
    }

    public function decode($raw_text) {
        //wfDebug("Internal encoding: ".mb_internal_encoding());
        //wfDebug("UTF-8 compatible: ".mb_check_encoding($raw_text, "UTF-8"));
        //wfDebug("ISO-8859-1 compatible: ".mb_check_encoding($raw_text, "ISO-8859-1"));

        $usfm_segments = explode("\\", $raw_text);

        for($i = 0; $i < sizeof($usfm_segments); $i++) {

            $remaining = strpbrk($usfm_segments[$i], " \n");

            /*yil debug
            $this->usfm_text->printHtmlText("<br/>remaining: ");
            $this->usfm_text->printHtmlText($remaining);
            $this->usfm_text->printHtmlText("<br/>");*/

            if($remaining === false) {
                $raw_command = $usfm_segments[$i];
                $remaining   = '';
            }
            else {
                $raw_command = substr(
                    $usfm_segments[$i], 0,
                    strlen($usfm_segments[$i]) -
                    strlen($remaining)
                );
                $remaining   = trim($remaining, " \n\t\r\0");
                if(mb_substr($remaining, mb_strlen($remaining) - 1, 1) != "\xA0") {
                    $remaining .= " ";
                }
            }

            if($raw_command == '') {
                continue;
            }
            else {
                $pos    = mb_strpos($raw_command, '*');
                $cmdlen = mb_strlen($raw_command);

                if($pos) {
                    if(($pos + 1) < $cmdlen) {
                        $leftover = mb_substr($raw_command, $pos + 1, $cmdlen);
                        $remaining = $leftover . ' ' . $remaining;
                        $raw_command = mb_substr($raw_command, 0, $pos + 1);
                    }

                }

            }

            //filter out number digits from raw command
            $main_command_length = strcspn($raw_command, '0123456789');
            $command             = substr($raw_command, 0, $main_command_length);

            if(strlen($raw_command) > $main_command_length) {
                $level = strval(substr($raw_command, $main_command_length));
            }
            else {
                $level = 1;
            }

            //port it case by case basis
            if(($command == 'h') || (substr($command, 0, 2) == 'id') ||
                ($command == 'rem') || ($command == 'sts') ||
                (substr($command, 0, 3) == 'toc')
            ) {
                $this->renderIdentification($command, $level, $remaining);
            }
            elseif($command == 'ip') {

                $this->renderParagraph($command, $level, $remaining);

            }
            elseif(($command == 'is') || ($command == 'imt')) {

                $this->renderTitleOrHeadingOrLabel($command, $level, $remaining);

            }
            elseif((substr($command, 0, 1) == 'i') &&
                (substr($command, 0, 2) <> 'it')
            ) {
                $this->renderIntroduction($command, $level, $remaining);

            }
            elseif((substr($command, 0, 1) == 'm') &&
                ($command <> 'm') && ($command <> 'mi')
            ) {
                $this->renderTitleOrHeadingOrLabel($command, $level, $remaining);
            }
            elseif((substr($command, 0, 1) == 's') &&
                (substr($command, 0, 2) <> 'sc') &&
                (substr($command, 0, 3) <> 'sig') &&
                (substr($command, 0, 3) <> 'sls')
            ) {
                if($level == 5) {
                    //Yvonne substitue s5 with <hr>
                    $command .= $level;
                    $level = 1;
                    $this->renderGeneralCommand($command, $level, $remaining);
                }
                else {
                    $this->renderTitleOrHeadingOrLabel($command, $level, $remaining);
                }

            }
            elseif(($command == 'd') || (substr($command, 0, 1) == 'r')) {
                $this->renderTitleOrHeadingOrLabel($command, $level, $remaining);

            }
            elseif((substr($command, 0, 1) == 'c') ||
                (substr($command, 0, 1) == 'v')
            ) {
                $this->renderChapterOrVerse(
                    $raw_command, $command,
                    $level, $remaining
                );

            }
            elseif((substr($command, 0, 1) == 'q') &&
                (substr($command, 0, 2) <> 'qt')
            ) {
                $this->renderPoetry($command, $level, $remaining);
            }
            elseif((substr($command, 0, 1) == 'p') && ($command <> 'pb') &&
                (substr($command, 0, 2) <> 'pn') &&
                (substr($command, 0, 3) <> 'pro')
            ) {
                $this->renderParagraph($command, $level, $remaining);

            }
            elseif((substr($command, 0, 1) == 't') &&
                (substr($command, 0, 2) <> 'tl')
            ) {
                $this->renderTable($command, $remaining);

            }
            elseif(($command == 'b') || ($command == 'cls') ||
                (substr($command, 0, 2) == 'li') ||
                ($command == 'm') || ($command == 'mi') ||
                ($command == 'nb')
            ) {
                $this->renderParagraph($command, $level, $remaining);
            }
            elseif((substr($command, 0, 1) == 'f') &&
                (substr($command, 0, 3) <> 'fig')
            ) {
                $this->renderFootnoteOrCrossReference($raw_command, $remaining);

            }
            elseif(substr($command, 0, 1) == 'x') {
                $this->renderFootnoteOrCrossReference($raw_command, $remaining);
            }
            else {
                $this->renderOther($raw_command, $remaining);
            }
        }

        if (function_exists(normalizer_normalize))
         return normalizer_normalize($this->usfm_text->getAndClearHtmlText());
        else
         return $this->usfm_text->getAndClearHtmlText();
    }

    protected function renderIdentification($command, $level, $remaining) {
        $this->displayUnsupportedCommand($command, $level, $remaining);
    }

    protected function renderIntroduction($command, $level, $remaining) {
        $this->displayUnsupportedCommand($command, $level, $remaining);
    }

    protected function renderTitleOrHeadingOrLabel($command, $level, $remaining) {
        $this->renderGeneralCommand($command, $level, $remaining);
    }

    protected function renderChapterOrVerse($raw_command, $command, $level, $remaining) {
        $remaining = trim($remaining, " ");
        if((substr($command, 0, 1) == 'v') &&
            (strlen($raw_command) == strlen($command))
        ) {
            $level = $this->extractSubCommand($remaining);
        }
        switch($command) {
            case 'c':
                $this->usfm_text->setChapterNumber($remaining);
                break;
            case 'ca':
                $this->usfm_text->setAlternateChapterNumber($remaining);
                break;
            case 'cl':
                // 10 NOV 2015, Phil Hopper, Issue #368: This is not currently needed on door43.org
                //$this->usfm_text->setChapterLabel($remaining);
                break;
            case 'cp':
                $this->usfm_text->setPublishedChapterNumber($remaining);
                break;
            case 'cd':
                $this->switchParagraph($command, $level);
                $this->usfm_text->printHtmlText($remaining);
                break;
            case 'v':
                $this->usfm_text->setVerseNumber($level);
                $this->usfm_text->printHtmlText($remaining);
                break;
            case 'va':
                //yil verse_number is not initialized
                //$this->usfm_text->setAlternateVerseNumber($verse_number);
                break;
            case 'vp':
                //yil $verse_number is not initialized
                //$this->usfm_text->setPublishedChapterNumber($verse_number);
                break;
            default:
                $this->usfm_text->printHtmlText($remaining);
        }
    }

    protected function renderPoetry($command, $level, $remaining) {
        $this->is_poetry = true;
        $this->renderGeneralCommand($command, $level, $remaining);
    }

    //yil added case for 'b' to close out paragraph
    protected function renderParagraph($command, $level, $remaining) {
        switch($command) {
            case 'nb':
                $this->usfm_text->flushPendingDropCapNumeral(true);
                $this->usfm_text->printHtmlText($remaining);
                break;
            case 'li':
                $this->usfm_text->switchListLevel($level);
                $this->usfm_text->printHtmlText("<li class='usfm'>" . $remaining);
                break;
            case 'b':
                $this->renderGeneralCommand($command, $level, $remaining);

                if($this->is_poetry) {
                    $this->switchParagraph('m', 1);
                    $this->is_poetry = false;
                }
                break;
            default:
                $this->renderGeneralCommand($command, $level, $remaining);
        }
    }

    protected function renderTable($command, $remaining) {
        switch($command) {
            case 'tr':
                $this->usfm_text->flushPendingTableColumns();
                break;
            case 'th':
                $this->usfm_text->insertTableColumn(true, false, $remaining);
                break;
            case 'thr':
                $this->usfm_text->insertTableColumn(true, true, $remaining);
                break;
            case 'tc':
                $this->usfm_text->insertTableColumn(false, false, $remaining);
                break;
            case 'tcr':
                $this->usfm_text->insertTableColumn(false, true, $remaining);
        }
    }

    protected function renderFootnoteOrCrossReference($command, $remaining) {
        switch($command) {
            case 'x':
            case 'f':
            case 'fe':
                if(substr($remaining, 1, 1) == ' ') {
                    $this->extractSubCommand($remaining);
                }
                if((mb_strlen($remaining) <= self::MAX_SELAH_CROSS_REFERENCES_LENGTH)
                    && (strpos($remaining, ' ') !== false) && ($command = 'x')
                ) {
                    $this->is_selah_cross_reference = true;
                    $this->renderGeneralCommand($command, 1, $remaining);
                }
                else {
                    $this->is_selah_cross_reference = false;
                    $this->usfm_text->newFooterEntry();
                    //$this->usfm_text->printHtmlTextToFooter($remaining);
                    $this->usfm_text->printHtmlText($remaining);
                }
                break;
            case 'x*':
            case 'f*':
            case 'fe*':
                if($this->is_selah_cross_reference) {
                    $this->renderGeneralCommand($command, 1, $remaining);
                }
                else {
                    $this->usfm_text->closeFooterEntry();
                    $this->usfm_text->printHtmlText($remaining);
                }
                break;
            case 'fk':
            case 'xk':
                //$this->usfm_text
                //     ->printHtmlTextToFooter(netscapeCapitalize($remaining));
                $this->usfm_text
                    ->printHtmlText(netscapeCapitalize($remaining));
                break;
            default:
                if(array_key_exists(
                    $command,
                    $this->footnote_substitution_table
                )) {
                    $setting   = $this->footnote_substitution_table[$command];
                    $remaining = $setting[self::BEFORE_REMAINING] . $remaining .
                        $setting[self::AFTER_REMAINING];
                }
                //$this->usfm_text->printHtmlTextToFooter($remaining);
                $this->usfm_text->printHtmlText($remaining);
        }
    }

    protected function renderOther($command, $remaining) {
        switch($command) {
            case 'nd':
                $this->usfm_text->printHtmlText(netscapeCapitalize($remaining));
                break;
            case 'add': //Yvonne processing add and add* tag here to fix space before punctuation problem
                $this->renderGeneralCommand($command, 1, trim($remaining)); //get rid of space at the end
                break;
            case 'add*': //do not add space if remaining start with punctuation
                if(ctype_punct(substr($remaining, 0, 1))) {
                    $this->usfm_text->printHtmlText($remaining);
                }
                else {
                    $this->usfm_text->printHtmlText(" " . $remaining);
                }

                break;
            default:
                $this->renderGeneralCommand($command, 1, $remaining);
        }
    }

    protected function displayUnsupportedCommand($command, $level,
                                                 $remaining) {
        if($level > 1) {
            $command = $command . $level;
        }
        //yil debug
        //$this->usfm_text
        //        ->printHtmlText(" USFMTag alert: Encountered unsupported command:".$command.' '.$remaining."\n");
        $this->usfm_text
            ->printHtmlText("<!-- usfm:\\" . $command . ' ' . $remaining . " -->\n");
    }

    protected function renderGeneralCommand($command, $level,
                                            $remaining) {

        if(array_key_exists($command, $this->substitution_table)) {
            $html_command = $this->substitution_table[$command];
            if(sizeof($html_command) > 1) {
                $this->usfm_text
                    ->printItalicsToBody(
                        $html_command[self::IF_NORMAL],
                        $html_command[self::IF_ITALIC_PARAGRAPH]
                    );
            }
            else {
                $this->usfm_text->printHtmlText($html_command[self::IF_NORMAL]);
            }
            $this->usfm_text->printHtmlText($remaining);

        }
        elseif(array_key_exists($command, $this->paragraph_settings)) {

            $this->switchParagraph($command, $level);
            $this->usfm_text->printHtmlText($remaining);
        }
        elseif(array_key_exists($command, $this->title_settings)) {
            $this->printTitle($command, $level, $remaining);
        }
        else {
            $this->displayUnsupportedCommand($command, $level, $remaining);
        }
    }

    private function extractSubCommand(&$remaining) {
        $second_whitespace = strpos($remaining, ' ');
        if($second_whitespace === false) {
            $second_whitespace = strlen($remaining);
        }
        $result    = substr($remaining, 0, $second_whitespace);
        $remaining = substr($remaining, $second_whitespace + 1);
        return $result;
    }

    private function switchParagraph($command, $level) {

        $setting = $this->paragraph_settings[$command];
        $this->usfm_text
            ->switchParagraph(
                $level + $setting[self::BASE_LEVEL] - 1,
                $setting[self::IS_ITALIC],
                $setting[self::ALIGNMENT],
                $setting[self::PARAGRAPH_CLASS]
            );
    }

    private function printTitle($command, $level, $content) {
        $setting = $this->title_settings[$command];
        $this->usfm_text
            ->printTitle(
                $level + $setting[self::BASE_LEVEL] - 1,
                $setting[self::IS_ITALIC], $content
            );

    }
}

function netscapeCapitalize($raw_text) {
    // Uppercase all letters, but make the first letter of every word bigger than
    // the rest, i.e. style of headings in the original Netscape Navigator website
    $words = explode(' ', strtoupper($raw_text));
    //wfDebug(sizeof($words));
    for($i = 0; $i < sizeof($words); $i++) {
        if(mb_strlen($words[$i]) > 1) {
            $words[$i] = mb_substr($words[$i], 0, 1) . "<small>" .
                mb_substr($words[$i], 1) . "</small>";
        }
        //wfDebug($words[$i]);
    }
    return implode(' ', $words);
}
?>
