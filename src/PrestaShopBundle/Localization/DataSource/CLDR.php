<?php
/**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShopBundle\Localization\DataSource;

use PrestaShopBundle\Localization\CLDR\DataReader;
use PrestaShopBundle\Localization\CLDR\DataReaderInterface;
use PrestaShopBundle\Localization\CLDR\LocaleData;
use PrestaShopBundle\Localization\Exception\Exception;
use PrestaShopBundle\Localization\Exception\InvalidArgumentException;

class CLDR implements DataSourceInterface
{
    const CLDR_ROOT = 'localization/CLDR/';
    const CLDR_MAIN = 'localization/CLDR/core/common/main/';

    /**
     * The contextual locale code.
     *
     * @var string
     */
    protected $localeCode;

    /**
     * The CLDR data reader (reads the CLDR data xml files)
     *
     * @var DataReaderInterface
     */
    protected $reader;

    /**
     * CLDR constructor.
     *
     * @param DataReader $reader
     */
    public function __construct(DataReader $reader)
    {
        $this->setReader($reader);
    }

    /**
     * Get the locale code
     *
     * @return string
     */
    public function getLocaleCode()
    {
        return $this->localeCode;
    }

    /**
     * Set the locale code
     *
     * @param string $localeCode
     *
     * @return $this
     */
    public function setLocaleCode($localeCode)
    {
        $this->localeCode = (string)$localeCode;

        return $this;
    }

    /**
     * Get the data reader
     *
     * @return DataReaderInterface
     * @throws Exception
     */
    public function getReader()
    {
        if (!isset($this->reader)) {
            throw new Exception("Data reader has not been set");
        }

        return $this->reader;
    }

    /**
     * Set the data reader
     *
     * @param DataReaderInterface $reader
     *
     * @return $this
     */
    public function setReader(DataReaderInterface $reader)
    {
        $this->reader = $reader;

        return $this;
    }

    /**
     * Get currency data by internal database identifier
     *
     * @param int $id
     *
     * @return array The currency data
     * @throws InvalidArgumentException
     */
    public function getLocaleById($id)
    {
        if (!is_int($id)) {
            throw new InvalidArgumentException('$id must be an integer');
        }

        return null;
    }

    /**
     * Get locale data by code (either language code or IETF locale tag)
     *
     * @param string $code
     *
     * @return array The locale data
     */
    public function getLocaleByCode($code)
    {
        return $this->getReader()->getLocaleByCode($code);
    }

    /**
     * Create a new locale in data source
     *
     * @param LocaleData $localeData
     *
     * @return int The id of newly created locale
     */
    public function createLocale(LocaleData $localeData)
    {
        // No write in CLDR data files
        return $localeData->id;
    }

    /**
     * Update an existing locale in data source
     *
     * @param LocaleData $localeData
     *
     * @return LocaleData The saved item
     */
    public function updateLocale(LocaleData $localeData)
    {
        // No write in CLDR data files
        return $localeData;
    }

    /**
     * Delete an existing locale in data source
     *
     * @param LocaleData $localeData
     *
     * @return bool True if deletion was successful (be it soft or hard)
     */
    public function deleteLocale(LocaleData $localeData)
    {
        // No deletion in CLDR data files
        return true;
    }
}