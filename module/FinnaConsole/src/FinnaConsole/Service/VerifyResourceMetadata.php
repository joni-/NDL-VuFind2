<?php
/**
 * Console service for verifying metadata of saved records.
 *
 * PHP version 5
 *
 * Copyright (C) The National Library of Finland 2016-2017.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace FinnaConsole\Service;
use Finna\Db\Table\Resource;

/**
 * Console service for verifying metadata of saved records.
 *
 * @category VuFind
 * @package  Service
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class VerifyResourceMetadata extends AbstractService
    implements ConsoleServiceInterface
{
    /**
     * Resource table.
     *
     * @var Resource
     */
    protected $resourceTable;

    /**
     * Date converter
     *
     * @var DateConverter
     */
    protected $dateConverter;

    /**
     * Record loader
     *
     * @var \VuFind\Record\Loader
     */
    protected $recordLoader;

    /**
     * SearchRunner
     *
     * @var SearchRunner
     */
    protected $searchRunner = null;

    /**
     * Constructor
     *
     * @param Resource               $resourceTable Resource table
     * @param \VuFind\Date\Converter $dateConverter Date converter
     * @param \VuFind\Record\Loader  $recordLoader  Record loader
     */
    public function __construct(Resource $resourceTable,
        \VuFind\Date\Converter $dateConverter, \VuFind\Record\Loader $recordLoader
    ) {
        $this->resourceTable = $resourceTable;
        $this->dateConverter = $dateConverter;
        $this->recordLoader = $recordLoader;
    }

    /**
     * Run service.
     *
     * @param array $arguments Command line arguments.
     *
     * @return boolean success
     */
    public function run($arguments)
    {
        $this->msg('Resource metadata verification started');
        $count = $fixed = 0;
        $resources = $this->resourceTable->findMissingMetadata();
        if (!$resources) {
            $this->msg('No resources with missing metadata found');
            return true;
        }

        $count = 0;
        $fixed = 0;
        foreach ($resources as $resource) {
            try {
                $driver = $this->recordLoader
                    ->load($resource->record_id, $resource->source);
                $resource->assignMetadata($driver, $this->dateConverter)->save();
                ++$fixed;
            } catch (\Exception $e) {
                $this->msg(
                    'Unable to load metadata for record '
                    . "{$resource->source}:{$resource->record_id}: "
                    . $e->getMessage()
                );
            } catch (\TypeError $e) {
                $this->msg(
                    'Unable to load metadata for record '
                    . "{$resource->source}:{$resource->record_id}: "
                    . $e->getMessage()
                );
            }
            ++$count;
            if ($count % 1000 == 0) {
                $this->msg("$count resources checked, $fixed fixed");
            }
        }

        $this->msg(
            "Resource metadata verification completed with $count resources checked,"
            . " $fixed fixed"
        );
        return true;
    }
}
