<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\HelperManager;

class IblockSchema extends AbstractSchema
{

    private $cache = array();

    /**@var HelperManager */
    private $helper;

    protected function initialize() {
        $this->helper = new HelperManager();
    }

    public function export() {

        $this->deleteSchema('iblock_types');
        $this->deleteSchemas('iblocks');

        $types = $this->helper->Iblock()->getIblockTypes();
        $exportTypes = array();
        foreach ($types as $type) {
            $exportTypes[] = $this->helper->Iblock()->exportIblockType($type['ID']);
        }

        $this->saveSchema('iblock_types', array(
            'items' => $exportTypes
        ));

        $iblocks = $this->helper->Iblock()->getIblocks();
        foreach ($iblocks as $iblock) {
            if (!empty($iblock['CODE'])) {
                $this->saveSchema('iblocks/' . $iblock['IBLOCK_TYPE_ID'] . '-' . $iblock['CODE'], array(
                    'iblock' => $this->helper->Iblock()->exportIblock($iblock['ID']),
                    'fields' => $this->helper->Iblock()->exportIblockFields($iblock['ID']),
                    'props' => $this->helper->Iblock()->exportProperties($iblock['ID']),
                    'element_form' => $this->helper->AdminIblock()->exportElementForm($iblock['ID'])
                ));
            }
        }

        $this->out('schema saved to %s', $this->getSchemaDir(true));
    }

    public function import() {
        $schemaTypes = $this->loadSchema('iblock_types');
        $schemaIblocks = $this->loadSchemas('iblocks/');

        foreach ($schemaTypes['items'] as $type) {
            $this->addToQueue('saveIblockType', $type);
        }

        foreach ($schemaIblocks as $schemaIblock) {
            $iblockId = $this->getIblockId($schemaIblock['iblock']);

            $this->addToQueue('saveIblock', $iblockId, $schemaIblock['iblock']);
            $this->addToQueue('saveIblockFields', $iblockId, $schemaIblock['fields']);
            $this->addToQueue('saveElementForm', $iblockId, $schemaIblock['element_form']);
        }

        foreach ($schemaIblocks as $schemaIblock) {
            $iblockId = $this->getIblockId($schemaIblock['iblock']);

            foreach ($schemaIblock['props'] as $prop) {
                $this->addToQueue('saveProperty', $iblockId, $prop);
            }
        }
    }


    protected function saveIblockType($type) {
        $exists = $this->helper->Iblock()->exportIblockType($type['ID']);
        if ($exists != $type) {
            $this->helper->Iblock()->saveIblockType($type);
            $this->out('iblock type %s saved', $type['ID']);
        } else {
            $this->out('iblock type %s is equal', $type['ID']);
        }
    }

    protected function saveIblock($iblockId, $iblock) {
        $exists = $this->helper->Iblock()->exportIblock($iblockId);
        if ($exists != $iblock) {
            $this->helper->Iblock()->saveIblock($iblock);
            $this->out('iblock %s saved', $iblockId);
        } else {
            $this->out('iblock %s is equal', $iblockId);
        }
    }


    protected function saveIblockFields($iblockId, $fields) {
        $exists = $this->helper->Iblock()->exportIblockFields($iblockId);
        if ($exists != $fields) {
            $this->helper->Iblock()->saveIblockFields($iblockId, $fields);
            $this->out('iblock %s fields saved', $iblockId);
        } else {
            $this->out('iblock %s fields equal', $iblockId);
        }
    }

    protected function saveElementForm($iblockId, $elementForm) {
        $exists = $this->helper->AdminIblock()->exportElementForm($iblockId);
        if ($exists != $elementForm) {
            $this->helper->AdminIblock()->saveElementForm($iblockId, $elementForm);
            $this->out('iblock %s admin form saved', $iblockId);
        } else {
            $this->out('iblock %s admin form equal', $iblockId);
        }
    }


    protected function saveProperty($iblockId, $property) {
        $exists = $this->helper->Iblock()->exportProperty($iblockId, $property['CODE']);
        if ($exists != $property) {
            $this->helper->Iblock()->saveProperty($iblockId, $property);
            $this->out('iblock %s property %s saved', $iblockId, $property['CODE']);
        } else {
            $this->out('iblock %s property %s equal', $iblockId, $property['CODE']);
        }
    }


    protected function deleteProperty($iblockId, $propertyCode) {
        $this->helper->Iblock()->deletePropertyIfExists($iblockId, $propertyCode);
    }

    protected function deleteIblockType($typeId) {
        $this->helper->Iblock()->deleteIblockType($typeId);
    }

    protected function deleteIblock($iblockId) {
        $this->helper->Iblock()->deleteIblock($iblockId);
    }

    protected function findValue($value, $haystack, $haystackKey) {
        foreach ($haystack as $item) {
            if ($item[$haystackKey] == $value) {
                return $item;
            }
        }
        return false;
    }

    protected function getIblockId($iblock) {
        $key = 'ib' . $iblock['IBLOCK_TYPE_ID'] . $iblock['CODE'];

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->helper->Iblock()->getIblockId(
                $iblock['CODE'],
                $iblock['IBLOCK_TYPE_ID']
            );
        }

        return $this->cache[$key];

    }

}