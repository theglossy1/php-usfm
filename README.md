# php-usfm
[USFM](http://paratext.org/about/usfm) to Simple HTML Converter Using PHP

This is based on a project initiated by Rusmin Soetjipto for MediaWiki 
and ported to DokuWiki by Yvonne Lu
[[GitHub Project]](https://github.com/unfoldingWord-dev/Dokuwiki-USFMTag).

It is designed to operate outside of any framework, and to produce simple html that can be
styled with CSS. Included in this repository is the file containing all three php classes.
The sample/ directory contains a sample CSS, a simple index.php wrapper, and Genesis 1 from
the British Edition of the [World English Bible](https://worldenglishbible.org/).

## Usage tips
The constant `FOOTNOTE_STYLE`, if set to 1, will cause the footnotes to be listed by number
(i.e., [1] for the first one, [2] for the second one, [27] for the 27th one, etc). The default 
behavior is to list them by letter (i.e., [a] for the first one, [b] for the second one, [aa] 
for the 27th one, etc.

### Example
```
define('FOOTNOTE_STYLE',1);
echo $tagDecode->decode($sfm);
```
