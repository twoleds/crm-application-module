<?php

namespace Crm\ApplicationModule;

trait DateFieldsProcessorTrait
{
    public function processDateFields($fields)
    {
        foreach ($fields as $i => $field) {
            if ($field instanceof \DateTime) {
                $fields[$i] = $field->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }
        }

        return $fields;
    }
}
