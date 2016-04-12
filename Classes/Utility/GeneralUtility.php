<?php

namespace MASK\Mask\Utility;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Benjamin Butschell <bb@webprofil.at>, WEBprofil - Gernot Ploiner e.U.
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * General useful methods
 *
 * @author Benjamin Butschell <bb@webprofil.at>
 */
class GeneralUtility
{

    /**
     * StorageRepository
     *
     * @var \MASK\Mask\Domain\Repository\StorageRepository
     */
    protected $storageRepository;

    /**
     * @param \MASK\Mask\Domain\Repository\StorageRepository $storageRepository
     */
    public function __construct(\MASK\Mask\Domain\Repository\StorageRepository $storageRepository = NULL)
    {
        if (!$storageRepository) {
            $this->storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('MASK\\Mask\\Domain\\Repository\\StorageRepository');
        } else {
            $this->storageRepository = $storageRepository;
        }
    }

    /**
     * Checks if a $evalValue is set in a field
     *
     * @param string $fieldKey TCA Type
     * @param string $evalValue value to search for
     * @param string $type elementtype
     * @return boolean $evalValue is set
     * @author Benjamin Butschell <bb@webprofil.at>
     */
    public function isEvalValueSet($fieldKey, $evalValue, $type = "tt_content")
    {
        $storage = $this->storageRepository->load();
        $found = FALSE;
        if ($storage[$type]["tca"][$fieldKey]["config"]["eval"] != "") {
            $evals = explode(",", $storage[$type]["tca"][$fieldKey]["config"]["eval"]);
            foreach ($evals as $index => $eval) {
                $evals[$index] = strtolower($eval);
            }
            $found = array_search(strtolower($evalValue), $evals) !== FALSE;
        }
        return $found;
    }

    /**
     * Checks if a $evalValue is set in a field
     *
     * @param string $fieldKey TCA Type
     * @param string $evalValue value to search for
     * @param string $type elementtype
     * @return boolean $evalValue is set
     * @author Benjamin Butschell <bb@webprofil.at>
     */
    public function isBlindLinkOptionSet($fieldKey, $evalValue, $type = "tt_content")
    {
        $storage = $this->storageRepository->load();
        $found = FALSE;
        if ($storage[$type]["tca"][$fieldKey]["config"]["wizards"]["link"]["params"]["blindLinkOptions"] != "") {
            $evals = explode(",", $storage[$type]["tca"][$fieldKey]["config"]["wizards"]["link"]["params"]["blindLinkOptions"]);
            $found = array_search(strtolower($evalValue), $evals) !== FALSE;
        }
        return $found;
    }

    /**
     * replace keys
     *
     * @author Gernot Ploiner <gp@webprofil.at>
     * @return array
     */
    public function replaceKey($data, $replace_key, $key = "--key--")
    {
        foreach ($data as $elem_key => $elem) {
            if (is_array($elem)) {
                $data[$elem_key] = $this->replaceKey($elem, $replace_key);
            } else {
                if ($data[$elem_key] == $key) {
                    $data[$elem_key] = $replace_key;
                }
            }
        }
        return $data;
    }

    /**
     * Searches an array of strings and returns the first string, that is not a tab
     * @param array $fields
     * @return $string
     */
    public function getFirstNoneTabField($fields)
    {
        if (count($fields)) {
            $potentialFirst = $fields[0];
            if (strpos($potentialFirst, "--div--") !== FALSE) {
                unset($fields[0]);
                return $this->getFirstNoneTabField($fields);
            } else {
                return $potentialFirst;
            }
        } else {
            return "";
        }
    }

    /**
     * Removes all the blank options from the tca
     * @param array $haystack
     * @return array
     */
    public function removeBlankOptions($haystack)
    {
        foreach ($haystack as $key => $value) {
            if (is_array($value)) {
                $haystack[$key] = $this->removeBlankOptions($haystack[$key]);
            }
            if (empty($haystack[$key])) {
                unset($haystack[$key]);
            }
        }
        return $haystack;
    }
}