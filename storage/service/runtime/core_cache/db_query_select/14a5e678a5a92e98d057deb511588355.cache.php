s:300:"SELECT  * FROM `short_url`  WHERE (`short_url`.`id` = 1 AND (`short_url`.`id` = 1 OR `short_url`.`short_url` LIKE '%Yh%' OR (`short_url`.`id` = 1 OR  (EXISTS(SELECT * FROM `shop_base`)))) AND (`short_url`.`id` IN (( SELECT id FROM `short_url` WHERE id<1000)) AND `short_url`.`id` > 'F{id} + 10'))    ";