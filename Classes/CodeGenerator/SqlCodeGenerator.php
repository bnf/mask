<?php

declare(strict_types=1);

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

namespace MASK\Mask\CodeGenerator;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Schema\SchemaException;
use MASK\Mask\Domain\Repository\StorageRepository;
use MASK\Mask\Utility\GeneralUtility as MaskUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generates all the sql needed for mask content elements
 */
class SqlCodeGenerator
{
    /**
     * StorageRepository
     *
     * @var StorageRepository
     */
    protected $storageRepository;

    /**
     * @var SchemaMigrator
     */
    protected $schemaMigrator;

    public function __construct(StorageRepository $storageRepository, SchemaMigrator $schemaMigrator)
    {
        $this->storageRepository = $storageRepository;
        $this->schemaMigrator = $schemaMigrator;
    }

    /**
     * Performs updates, adjusted function from extension_builder
     *
     * @param array $sqlStatements
     * @return array
     * @throws DBALException
     * @throws SchemaException
     * @throws StatementException
     * @throws UnexpectedSignalReturnValueTypeException
     */
    public function updateDatabase(): array
    {
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $schemaMigrator = GeneralUtility::makeInstance(SchemaMigrator::class);

        $sqlUpdateSuggestions = $schemaMigrator->getUpdateSuggestions($sqlStatements);
        $hasErrors = false;

        foreach ($sqlUpdateSuggestions as $connectionName => $updateConnection) {
            $connection = $connectionPool->getConnectionByName($connectionName);
            foreach ($updateConnection as $updateStatements) {
                foreach ($updateStatements as $statement) {
                    try {
                        $connection->exec($statement);
                    } catch (DBALException $exception) {
                        $hasErrors = true;
                        //@todo
//                        GeneralUtility::devlog(
//                            'SQL error',
//                            'mask',
//                            0,
//                            [
//                                'statement' => $statement,
//                                'error' => $exception->getMessage()
//                            ]);
                    }
                }
            }
        }

        if ($hasErrors) {
            return [
                'error' => 'Database could not be updated. Please check it in the update wizard of the install tool.'
            ];
        }

        return ['success' => 'Database was successfully updated.'];
    }

    /**
     * returns sql statements of all elements and pages and irre
     * @param array $json
     * @return array
     */
    public function getSqlByConfiguration($json): array
    {
        $sql_content = [];

        // Generate SQL-Statements
        foreach ($json as $type => $_) {
            if (!$json[$type]['sql'] ?? false) {
                continue;
            }

            foreach ($json[$type]['sql'] as $field) {
                foreach ($field ?? [] as $table => $fields) {
                    foreach ($fields ?? [] as $fieldKey => $definition) {
                        $fieldType = $this->storageRepository->getFormType($fieldKey, '', $table);
                        if ($fieldType === 'Inline' && !array_key_exists($fieldKey, $json)) {
                            continue;
                        }
                        $sql_content[] = 'CREATE TABLE ' . $table . " (\n\t" . $fieldKey . ' ' . $definition . "\n);\n";
                        // if this field is a content field, also add parent columns
                        if ($fieldType === 'Content') {
                            $sql_content[] = "CREATE TABLE tt_content (\n\t" . $fieldKey . '_parent' . ' ' . $definition . ",\n\t" . 'KEY ' . $fieldKey . ' (' . $fieldKey . '_parent,pid)' . "\n);\n";
                        }
                    }
                }
            }

            // If type/table is an irre table, then create table for it
            if (MaskUtility::isMaskIrreTable($type)) {
                $sql_content[] = 'CREATE TABLE ' . $type . " (

                         uid int(11) NOT NULL auto_increment,
                         pid int(11) DEFAULT '0' NOT NULL,

                         tstamp int(11) unsigned DEFAULT '0' NOT NULL,
                         crdate int(11) unsigned DEFAULT '0' NOT NULL,
                         cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
                         deleted SMALLINT unsigned DEFAULT '0' NOT NULL,
                         hidden SMALLINT unsigned DEFAULT '0' NOT NULL,
                         starttime int(11) unsigned DEFAULT '0' NOT NULL,
                         endtime int(11) unsigned DEFAULT '0' NOT NULL,
                         editlock SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
                         fe_group VARCHAR(255) DEFAULT '0' NOT NULL,

                         t3ver_oid int(11) DEFAULT '0' NOT NULL,
                         t3ver_id int(11) DEFAULT '0' NOT NULL,
                         t3ver_wsid int(11) DEFAULT '0' NOT NULL,
                         t3ver_label varchar(255) DEFAULT '' NOT NULL,
                         t3ver_state SMALLINT DEFAULT '0' NOT NULL,
                         t3ver_stage int(11) DEFAULT '0' NOT NULL,
                         t3ver_count int(11) DEFAULT '0' NOT NULL,
                         t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
                         t3ver_move_id int(11) DEFAULT '0' NOT NULL,
                         t3_origuid int(11) UNSIGNED DEFAULT '0' NOT NULL,

                         sys_language_uid int(11) DEFAULT '0' NOT NULL,
                         l10n_parent int(11) DEFAULT '0' NOT NULL,
                         l10n_source int(11) UNSIGNED DEFAULT '0' NOT NULL,
                         l10n_diffsource mediumblob,
                         l10n_state text,

                         parentid int(11) DEFAULT '0' NOT NULL,
                         parenttable varchar(255) DEFAULT '',
                         sorting int(11) DEFAULT '0' NOT NULL,

                         PRIMARY KEY (uid),
                         KEY parent (pid,sorting),
                         KEY t3ver_oid (t3ver_oid,t3ver_wsid),
                         KEY language (l10n_parent,sys_language_uid)
                     );\n";
            }
        }

        return $sql_content;
    }

    /**
     * Adds the SQL for all elements to the psr-14 AlterTableDefinitionStatementsEvent event.
     *
     * @param AlterTableDefinitionStatementsEvent $event
     */
    public function addDatabaseTablesDefinition(AlterTableDefinitionStatementsEvent $event): void
    {
        $json = $this->storageRepository->load();
        $sql = $this->getSqlByConfiguration($json);
        $event->setSqlData(array_merge($event->getSqlData(), $sql));
    }
}
