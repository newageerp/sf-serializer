<?php

namespace Newageerp\SfSerializer\Serializer;

use Doctrine\Common\Collections\Collection;

class ObjectSerializer
{
    public static function serializeRow($item, $fieldsToReturn)
    {
        $el = [
            'id' => $item->getId()
        ];
        foreach ($fieldsToReturn as $field) {
            if (mb_strpos($field, ':')) {
                $field = explode(":", $field);
                $getter1 = 'get' . $field[0];
                if (method_exists($item, $getter1)) {
                    $rel = $item->$getter1();
                    if ($rel instanceof Collection) {
                        $relFields = explode(",", $field[1]);

                        $el[$field[0]] = array_values($rel->map(function ($relItem) use ($relFields) {
                            return ObjectSerializer::serializeRow($relItem, $relFields);
                        })->toArray());
                    }
                }
            } else if (mb_strpos($field, '.')) {
                $field = explode(".", $field);
                $getter1 = 'get' . $field[0];
                $getter2 = 'get' . $field[1];

                if (method_exists($item, $getter1)) {
                    $rel = $item->$getter1();
                    if ($rel) {
                        if (is_array($rel)) {
                            $val = self::formatVal($rel[$field[1]]);

                            $el[$field[0]][$field[1]] = $val;
                        } else if (method_exists($rel, $getter2)) {
                            $el[$field[0]]['id'] = $rel->getId();

                            if (count($field) > 2) {
                                $getter3 = 'get' . $field[2];
                                $rel2 = $rel->$getter2();
                                if (is_array($rel2)) {
                                    $val = self::formatVal($rel2[$field[2]]);

                                    $el[$field[0]][$field[1]][$field[2]] = $val;

                                } else if ($rel2 && method_exists($rel2, $getter3)) {
                                    $val = self::formatVal($rel2->$getter3());

                                    $el[$field[0]][1]['id'] = $rel2->getId();
                                    $el[$field[0]][$field[1]][$field[2]] = $val;
                                }
                            } else {
                                $val = self::formatVal($rel->$getter2());

                                $el[$field[0]][$field[1]] = $val;
                            }
                        }
                    } else {
                        $el[$field[0]] = null;
                    }
                } else {
                    // $el[$field[0]][$field[1]] = 'NO METHOD';
                }
            } else {
                $getter = 'get' . $field;
                if (method_exists($item, $getter)) {
                    $val = self::formatVal($item->$getter());

                    $el[$field] = $val;
                }
            }
        }
        return $el;
    }

    protected static function formatVal($val)
    {
        if ($val instanceof \DateTime) {
            $val = $val->format('Y-m-d H:i');
        }
        if (is_object($val)) {
            $val = ['id' => $val->getId()];
        }
        return $val;
    }
}
