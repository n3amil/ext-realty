<?php

defined('TYPO3_cliMode') or die('You cannot run this script directly!');

setlocale(LC_NUMERIC, 'C');

/**
 * This class provides access via command-line interface.
 *
 * To run this script, use the following command in a console: '/[absolute path
 * of the TYPO3 installation]/typo3/cli_dispatch.phpsh openImmoImport'.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_cli
{
    /**
     * Calls the OpenImmo importer.
     *
     * @return void
     */
    public function main()
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();

        /** @var \tx_realty_openImmoImport $importer */
        $importer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_realty_openImmoImport::class);
        echo $importer->importFromZip();
    }
}

/** @var \tx_realty_cli $cli */
$cli = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_realty_cli::class);
$cli->main();
