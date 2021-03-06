<?php
namespace TYPO3\CMS\Backend\Template\Components;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * MetaInformation
 */
class MetaInformation
{
    /**
     * The recordArray.
     * Typically this is a page record
     *
     * @var array
     */
    protected $recordArray = [];

    /**
     * Set the RecordArray
     *
     * @param array $recordArray RecordArray
     */
    public function setRecordArray(array $recordArray)
    {
        $this->recordArray = $recordArray;
    }

    /**
     * Generate the page path for docHeader
     *
     * @return string The page path
     */
    public function getPath()
    {
        $pageRecord = $this->recordArray;
        $title = '';
        // Is this a real page
        if (is_array($pageRecord) && $pageRecord['uid']) {
            $title = substr($pageRecord['_thePathFull'], 0, -1);
            // Remove current page title
            $pos = strrpos($title, $pageRecord['title']);
            if ($pos !== false) {
                $title = substr($title, 0, $pos);
            }
        } elseif (!empty($pageRecord['combined_identifier'])) {
            try {
                $resourceObject = ResourceFactory::getInstance()->getInstance()->getObjectFromCombinedIdentifier($pageRecord['combined_identifier']);
                $title = $resourceObject->getStorage()->getName() . ':';
                $title .= $resourceObject->getParentFolder()->getReadablePath();
            } catch (ResourceDoesNotExistException $e) {
            } catch (InsufficientFolderAccessPermissionsException $e) {
            }
        }
        // Setting the path of the page
        // crop the title to title limit (or 50, if not defined)
        $beUser = $this->getBackendUser();
        $cropLength = empty($beUser->uc['titleLen']) ? 50 : $beUser->uc['titleLen'];
        $croppedTitle = GeneralUtility::fixed_lgd_cs($title, -$cropLength);
        if ($croppedTitle !== $title) {
            $pagePath = '<abbr title="' . htmlspecialchars($title) . '">' . htmlspecialchars($croppedTitle) . '</abbr>';
        } else {
            $pagePath = htmlspecialchars($title);
        }
        return $pagePath;
    }

    /**
     * Setting page icon with context menu + uid for docheader
     *
     * @return string Record info
     */
    public function getRecordInformation()
    {
        $recordInformations = $this->getRecordInformations();
        if (!empty($recordInformations)) {
            $recordInformation = $recordInformations['icon'] .
                ' <strong>' . htmlspecialchars($recordInformations['title']) . ($recordInformations['uid'] !== '' ? '&nbsp;[' . $recordInformations['uid'] . ']' : '') . '</strong>' .
                (!empty($recordInformations['additionalInfo']) ? ' ' . htmlspecialchars($recordInformations['additionalInfo']) : '');
        } else {
            $recordInformation = '';
        }
        return $recordInformation;
    }

    /**
     * Setting page icon
     *
     * @return string Record icon
     */
    public function getRecordInformationIcon()
    {
        $recordInformations = $this->getRecordInformations();
        if (!empty($recordInformations)) {
            $recordInformationIcon = $recordInformations['icon'];
        } else {
            $recordInformationIcon = null;
        }
        return $recordInformationIcon;
    }

    /**
     * Setting page title
     *
     * @return string Record title
     */
    public function getRecordInformationTitle()
    {
        $recordInformations = $this->getRecordInformations();
        if (!empty($recordInformations)) {
            $title = htmlspecialchars($recordInformations['title']);
        } else {
            $title = '';
        }

        // crop the title to title limit (or 50, if not defined)
        $beUser = $this->getBackendUser();
        $cropLength = empty($beUser->uc['titleLen']) ? 50 : $beUser->uc['titleLen'];
        $croppedTitle = GeneralUtility::fixed_lgd_cs($title, $cropLength);
        if ($croppedTitle !== $title) {
            $recordInformationTitle = htmlspecialchars($croppedTitle);
        } else {
            $recordInformationTitle = htmlspecialchars($title);
        }

        return $recordInformationTitle;
    }

    /**
     * Setting page uid
     *
     * @return null|int Record uid
     */
    public function getRecordInformationUid()
    {
        $recordInformations = $this->getRecordInformations();
        if (!empty($recordInformations)) {
            $recordInformationUid = $recordInformations['uid'];
        } else {
            $recordInformationUid = null;
        }
        return $recordInformationUid;
    }

    /**
     * Setting page array
     *
     * @return array Record info
     */
    protected function getRecordInformations()
    {
        $pageRecord = $this->recordArray;
        if (empty($pageRecord)) {
            return [];
        }

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $uid = '';
        $title = '';
        $additionalInfo = (!empty($pageRecord['_additional_info']) ? $pageRecord['_additional_info'] : '');
        // Add icon with context menu, etc:
        // If there IS a real page
        if (is_array($pageRecord) && $pageRecord['uid']) {
            $toolTip = BackendUtility::getRecordToolTip($pageRecord, 'pages');
            $iconImg = '<span ' . $toolTip . '>' . $iconFactory->getIconForRecord('pages', $pageRecord, Icon::SIZE_SMALL)->render() . '</span>';
            // Make Icon:
            $theIcon = BackendUtility::wrapClickMenuOnIcon($iconImg, 'pages', $pageRecord['uid']);
            $uid = $pageRecord['uid'];
            $title = BackendUtility::getRecordTitle('pages', $pageRecord);
        // If the module is about a FAL resource
        } elseif (is_array($pageRecord) && !empty($pageRecord['combined_identifier'])) {
            try {
                $resourceObject = ResourceFactory::getInstance()->getInstance()->getObjectFromCombinedIdentifier($pageRecord['combined_identifier']);
                $fileMountTitle = $resourceObject->getStorage()->getFileMounts()[$resourceObject->getIdentifier()]['title'];
                $title = $fileMountTitle ?: $resourceObject->getName();
                // If this is a folder but not in within file mount boundaries this is the root folder
                if ($resourceObject instanceof FolderInterface && !$resourceObject->getStorage()->isWithinFileMountBoundaries($resourceObject)) {
                    $iconImg = '<span title="' . htmlspecialchars($title) . '">' . $iconFactory->getIconForResource(
                        $resourceObject,
                        Icon::SIZE_SMALL,
                        null,
                        ['mount-root' => true]
                    )->render() . '</span>';
                } else {
                    $iconImg = '<span title="' . htmlspecialchars($title) . '">' . $iconFactory->getIconForResource(
                        $resourceObject,
                        Icon::SIZE_SMALL
                    )->render() . '</span>';
                }
                $theIcon = BackendUtility::wrapClickMenuOnIcon($iconImg, 'sys_file', $pageRecord['combined_identifier']);
            } catch (ResourceDoesNotExistException $e) {
                $theIcon = '';
            }
        } else {
            // On root-level of page tree
            // Make Icon
            $iconImg = '<span title="' .
                htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) .
                '">' .
                $iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render() . '</span>';
            if ($this->getBackendUser()->isAdmin()) {
                $theIcon = BackendUtility::wrapClickMenuOnIcon($iconImg, 'pages', 0);
            } else {
                $theIcon = $iconImg;
            }
            $uid = '0';
            $title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        }
        // returns array for icon, title, uid and additional info
        return [
            'uid' => $uid,
            'icon' => $theIcon,
            'title' => htmlspecialchars($title),
            'additionalInfo' => $additionalInfo
        ];
    }

    /**
     * Get LanguageService Object
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Get the Backend User Object
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
