<?php
/**
 * This file contains class::StrideLength
 * @package Runalyze
 */

namespace Runalyze\Dataset\Keys;

use Runalyze\Dataset\Context;
use Runalyze\Dataset\SummaryMode;

/**
 * Dataset key: OpenTab
 * Open the activity in a new Browser tab/window
 * 
 * @author TSC
 * @package Runalyze\Dataset\Keys
 */
class OpenTab extends AbstractKey
{
	/**
	 * Enum id
	 * @return int
	 */
	public function id()
	{
		return \Runalyze\Dataset\Keys::OPEN_TAB;
	}

	/**
	 * Database key
	 * @return string
	 */
	public function column()
	{
		return '';
	}

    /**
     * @return bool
     */
    public function isInDatabase()
    {
        return false;
    }

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function label()
	{
		return __('Open new tab');
	}

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function shortLabel()
	{
		return '';
	}


    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function hasPrivacyOption() {
        return false;
    }

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function description()
	{
		return __(
			'Open the activity in a new browser tab/window'
		);
	}

	/**
	 * Get string to display this dataset value
	 * @param \Runalyze\Dataset\Context $context
	 * @return string
	 */
	public function stringFor(Context $context)
	{
		$id = $context->activity()->id();
        if ($id > 0) {
            return '<a href="/dashboard?id=' . $id . '#statistics-scrolltag" target="_blank" onclick="event.stopPropagation()">' . $this->getCodeForIcon() .'</a>';
        }

        return '';
	}

    public function stringForExample(Context $context)
    {
        return '<span class="link">' . $this->getCodeForIcon() . '</span>';
    }

    /**
     * @return string
     */
    protected function getCodeForIcon()
    {
        return '<i class="fa fa-fw fa-external-link" style="font-weight: 900;"></i>';
    }

	/**
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function cssClass()
	{
		return 'small';
	}
}
