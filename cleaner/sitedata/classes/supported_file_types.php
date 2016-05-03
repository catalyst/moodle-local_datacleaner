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
 * @package    cleaner_sitedata
 * @author     Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @copyright  2015 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace cleaner_sitedata;

defined('MOODLE_INTERNAL') || die;

/**
 * Note: I'm not sure how to get this class to autoload as a sub-plugin.
 * @author     Ghada El-Zoghbi <ghada@catalyst-au.net>
 * @copyright  2015 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleaner_sitedata_supported_file_types {

    // Here's a list of file types for reference:
    // http://www.freeformatter.com/mime-types-list.html .
    const MIMETYPE_7Z   = 'application/x-7z-compressed';
    const MIMETYPE_AVI  = 'video/avi';
    const MIMETYPE_BZ   = 'application/x-bzip';
    const MIMETYPE_BZ2  = 'application/x-bzip2';
    const MIMETYPE_CSS  = 'text/css';
    const MIMETYPE_CSV  = 'text/csv';
    const MIMETYPE_DOC  = 'application/msword';
    const MIMETYPE_DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    const MIMETYPE_FLV  = 'video/x-flv';
    const MIMETYPE_GIF  = 'image/gif';
    const MIMETYPE_GTAR = 'application/x-gtar';
    const MIMETYPE_GZ   = 'application/g-zip';
    const MIMETYPE_HTML = 'text/html';
    const MIMETYPE_JPEG = 'image/jpeg';
    const MIMETYPE_JS   = 'application/x-javascript';
    const MIMETYPE_MOV  = 'video/quicktime';
    const MIMETYPE_MP3  = 'audio/mp3';
    const MIMETYPE_MP4  = 'video/mp4';
    const MIMETYPE_ODB  = 'application/vnd.oasis.opendocument.database';
    const MIMETYPE_ODC  = 'application/vnd.oasis.opendocument.chart';
    const MIMETYPE_ODF  = 'application/vnd.oasis.opendocument.formula';
    const MIMETYPE_ODG  = 'application/vnd.oasis.opendocument.graphics';
    const MIMETYPE_ODI  = 'application/vnd.oasis.opendocument.image';
    const MIMETYPE_ODM  = 'application/vnd.oasis.opendocument.text-master';
    const MIMETYPE_ODP  = 'application/vnd.oasis.opendocument.presentation';
    const MIMETYPE_ODS  = 'application/vnd.oasis.opendocument.spreadsheet';
    const MIMETYPE_ODT  = 'application/vnd.oasis.opendocument.text';
    const MIMETYPE_PDF  = 'application/pdf';
    const MIMETYPE_PNG  = 'image/png';
    const MIMETYPE_PPT  = 'application/vnd.ms-powerpoint';
    const MIMETYPE_PPTX = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
    const MIMETYPE_RAR  = 'application/x-rar-compressed';
    const MIMETYPE_RTF  = 'text/rtf';
    const MIMETYPE_SWF  = 'application/x-shockwave-flash';
    const MIMETYPE_TAR  = 'application/x-tar';
    const MIMETYPE_TIF  = 'image/tiff';
    const MIMETYPE_TXT  = 'text/plain';
    const MIMETYPE_WMV  = 'video/x-ms-wmv';
    const MIMETYPE_XLS  = 'application/vnd.ms-excel';
    const MIMETYPE_XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    const MIMETYPE_XML  = 'application/xml';
    const MIMETYPE_ZIP  = 'application/zip';
    const MIMETYPE_NONE = '';

    const MIMETYPE_DEFAULT = 'text/plain';

    private $supportedfiletypes = array();
    private $placeholder = 'placeholder';
    private $defaultext = '.txt';

    /**
     * Instantiate the class to build the supported file types array.
     */
    public function __construct() {
        $this->supportedfiletypes = array(
                        self::MIMETYPE_NONE => '--None--',
                        self::MIMETYPE_7Z   => '7z',
                        self::MIMETYPE_AVI  => 'avi',
                        self::MIMETYPE_BZ   => 'bz',
                        self::MIMETYPE_BZ2  => 'bz2',
                        self::MIMETYPE_CSS  => 'css',
                        self::MIMETYPE_CSV  => 'csv',
                        self::MIMETYPE_DOC  => 'doc',
                        self::MIMETYPE_DOCX => 'docx',
                        self::MIMETYPE_FLV  => 'flv',
                        self::MIMETYPE_GIF  => 'gif',
                        self::MIMETYPE_GTAR => 'gtar',
                        self::MIMETYPE_GZ   => 'gz',
                        self::MIMETYPE_HTML => 'htm, html',
                        self::MIMETYPE_JPEG => 'jpg, jpeg',
                        self::MIMETYPE_JS   => 'js',
                        self::MIMETYPE_MOV  => 'mov, moov',
                        self::MIMETYPE_MP3  => 'mp3',
                        self::MIMETYPE_MP4  => 'mp4',
                        self::MIMETYPE_ODB  => 'odb',
                        self::MIMETYPE_ODC  => 'odc',
                        self::MIMETYPE_ODF  => 'odf',
                        self::MIMETYPE_ODG  => 'odg',
                        self::MIMETYPE_ODI  => 'odi',
                        self::MIMETYPE_ODM  => 'odm',
                        self::MIMETYPE_ODP  => 'odp',
                        self::MIMETYPE_ODS  => 'ods',
                        self::MIMETYPE_ODT  => 'odt',
                        self::MIMETYPE_PDF  => 'pdf',
                        self::MIMETYPE_PNG  => 'png',
                        self::MIMETYPE_PPT  => 'ppt',
                        self::MIMETYPE_PPTX => 'pptx',
                        self::MIMETYPE_RAR  => 'rar',
                        self::MIMETYPE_RTF  => 'rtf',
                        self::MIMETYPE_SWF  => 'swf',
                        self::MIMETYPE_TAR  => 'tar',
                        self::MIMETYPE_TIF  => 'tif, tiff',
                        self::MIMETYPE_TXT  => 'txt',
                        self::MIMETYPE_WMV  => 'wmv',
                        self::MIMETYPE_XLS  => 'xls',
                        self::MIMETYPE_XLSX => 'xlsx',
                        self::MIMETYPE_XML  => 'xml',
                        self::MIMETYPE_ZIP  => 'zip',
        );
    }

    /**
     * Returns an array of available mime types and their corresponding file extensions.
     * @return array of availble mime types.
     */
    public function get_supported_file_types() {
        return $this->supportedfiletypes;
    }

    /**
     * Returns the file extension for the specified file type.
     * @param string $type - a mime type.
     * @return string extension. 'unknown' string if the type is unknown or unsupported.
     */
    public function get_file_extension_for_type($type) {
        if (isset($this->supportedfiletypes[$type])) {
            return $this->supportedfiletypes[$type];
        }
        return 'unknown';
    }

    /**
     * Returns the placeholder file name for the specified file type.
     * @param string $type - a mime type.
     * @return string the name of the placeholder file.
     */
    public function get_placeholder_file_name_for_type($type) {
        global $CFG;

        // Set the defaults.
        $filename = $this->placeholder . $this->defaultext;
        $mimetype = self::MIMETYPE_DEFAULT;

        // Trim and format the type. It may be in mixed case
        // coming from the database.
        $type = trim(strtolower($type));

        // Do we support this type?
        if (isset($this->supportedfiletypes[$type])) {
            // Yes. Now get the extension of the file.
            $ext = $this->supportedfiletypes[$type];

            // If there's more than one extension, check which file we have that is the placeholder.
            $extensions = explode(',', $ext);
            $extensions = array_map("trim", $extensions);

            foreach ($extensions as $extension) {
                if ($type == '') {
                    // This has no mimetype and therefore its extension is nothing.
                    $extension = '';
                } else if (substr($extension, 0) != '.') {
                    $extension = '.' . $extension;
                }
                $source = $CFG->dirroot . '/local/datacleaner/cleaner/sitedata/fixtures/' . $this->placeholder . $extension;

                if (file_exists($source)) {
                    // Found it. Set the filename and type so it overrides the default setting above.
                    $filename = $this->placeholder . $extension;
                    $mimetype = $type;
                    break;
                }
            }
        }
        // Return our results.
        return array($mimetype, $filename);
    }

}
