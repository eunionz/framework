s:174:"SELECT  (SELECT COUNT(* ) FROM `short_url`) AS `id1`, (SELECT COUNT(* ) FROM `short_url`) AS `id2`, LEFT(`short_url`.`short_url`,10) AS `n`, NOW AS `m` FROM `short_url`      ";