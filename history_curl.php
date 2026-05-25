<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('memory_limit', '2000M');

define('FOLDER_ROOT', '/data/vgh/');

include FOLDER_ROOT.'templates/clear_folders_history_insert.php';

include FOLDER_ROOT.'templates/pg_connect.php';


$sql = <<<SQL
with imp_partners as (
select   "uniquePartnerIdentifier" as partner_id
from pcs_ods.v_businesspartners
where "partnerBusinessType" = '{Technical importer}'
union 
select "uniquePartnerIdentifier" as partner_id
from pcs_ods.v_legalentities
where "isInternationalLegalEntity" = true
), imp_sups as(
select distinct s.supplier
FROM rms_p009qtzb_rms_ods.v_sups s
left join rms_p009qtzb_rms_ods.v_sups_cfa_ext imp 
on imp.supplier = s.supplier and imp.group_id = 26
left join  imp_partners bp 
on bp.partner_id = imp.number_14
where imp.varchar2_2 = 'IMP'
or bp.partner_id is not null)
, cte as (
SELECT DISTINCT item
, PRIMARY_SUPP 
FROM rms_p009qtzb_rms_ods.v_item_loc
WHERE status = 'A' 
and loc not in (351,352,397,396,399,66,168,1,398,395)
and loc_type = 'S'
union 
SELECT DISTINCT il.item , 
il.PRIMARY_SUPP 
FROM rms_p009qtzb_rms_ods.v_item_loc il
join imp_sups isp on isp.supplier = il.PRIMARY_SUPP 
union 
select distinct item
, primary_supp 
from rms_p009qtzb_rms_ods.v_item_loc_soh
where stock_on_hand > 0
and primary_supp % 100 = 15
and (
(loc / 1000) = 912
or 
loc = 912) 
)
SELECT distinct(isc.item)
   ,isc.supplier
    , isc.origin_country_id
    , isc.inner_pack_size
    , isc.supp_pack_size
    , isc.ti as tiprovider
    , isc.hi as hiprovider
    , isc.ti * isc.hi as tihi
       , im.create_datetime 
       , im.standard_uom 
       , im.dept
     , iscd_ea.WIDTH as eawidthprovider
     , iscd_ea.WIDTH as eawidthrms
     , iscd_ea.HEIGHT as eaheightprovider
     , iscd_ea.HEIGHT as eaheightrms
     , iscd_ea.LENGTH as ealengthprovider
     , iscd_ea.LENGTH as ealengthrms
     , iscd_ea.LWH_UOM as ealwh_uom 
     , iscd_ea.WIDTH*iscd_ea.HEIGHT*iscd_ea.LENGTH as eavolume
     , iscd_ea.net_weight as eanetweightprovider
     , iscd_ea.net_weight as eanetweightrms
     , iscd_ea.weight as eaweightprovider
     , iscd_ea.weight as eaweightrms
     , iscd_ea.WEIGHT_UOM as eaweight_uom
     , iscd_in.WIDTH as innerwidthprovider
     , iscd_in.WIDTH as innerwidthrms
     , iscd_in.HEIGHT as innerheightprovider
     , iscd_in.HEIGHT as innerheightrms
     , iscd_in.LENGTH as innerlengthprovider
     , iscd_in.LENGTH as innerlengthrms
     , iscd_in.LWH_UOM as innerlwhuomprovider
     , iscd_in.net_weight as innernetweightprovider
     , iscd_in.weight as innergrossweightprovider
     , iscd_in.weight as innergrossweightrms
     , iscd_in.WEIGHT_UOM as innerweightuom
     , iscd_ca.WIDTH as outerwidthprovider
     , iscd_ca.WIDTH as outerwidthrms
     , iscd_ca.HEIGHT as outerheightprovider
     , iscd_ca.HEIGHT as outerheightrms
     , iscd_ca.LENGTH as outerlengthprovider
     , iscd_ca.LENGTH as outerlengthrms
     , iscd_ca.LWH_UOM as outerlwhuom
     , iscd_ca.net_weight as outernetweightprovider
     , iscd_ca.weight as outergrossweightprovider
     , iscd_ca.weight as outergrossweightrms
     , iscd_ca.WEIGHT_UOM as outerweightuom
     , iscd_pa.weight as pallgrossweightprovider
     , iscd_pa.weight as pallgrossweightrms
     , iscd_pa.WEIGHT_UOM as pallweightuom
     , iscd_pa.WIDTH as pallwidthprovider
     , iscd_pa.WIDTH as pallwidthrms
     , iscd_pa.HEIGHT as pallheightprovider
     , iscd_pa.HEIGHT as pallheightrms
     , iscd_pa.LENGTH as palllengthprovider
     , iscd_pa.LENGTH as palllengthrms
     , iscd_pa.LWH_UOM as palllwhuom
from rms_p009qtzb_rms_ods.v_item_supp_country isc 
join cte c 
on c.item = isc.item and isc.supplier = c.PRIMARY_SUPP
join rms_p009qtzb_rms_ods.v_sups s on s.supplier = isc.supplier and s.sup_status = 'A'
          left join  rms_p009qtzb_rms_ods.v_item_supp_country_dim iscd_ca on isc.item=iscd_ca.item and isc.supplier=iscd_ca.supplier and isc.origin_country_id = iscd_ca.origin_country and iscd_ca.DIM_OBJECT = 'CA'
          left join  rms_p009qtzb_rms_ods.v_item_supp_country_dim iscd_in on isc.item=iscd_in.item and isc.supplier=iscd_in.supplier and isc.origin_country_id = iscd_in.origin_country and iscd_in.DIM_OBJECT = 'IN'
          left join  rms_p009qtzb_rms_ods.v_item_supp_country_dim iscd_ea on isc.item=iscd_ea.item and isc.supplier=iscd_ea.supplier and isc.origin_country_id = iscd_ea.origin_country and iscd_ea.DIM_OBJECT = 'EA' 
          left join  rms_p009qtzb_rms_ods.v_item_supp_country_dim iscd_pa on isc.item=iscd_pa.item and isc.supplier=iscd_pa.supplier and isc.origin_country_id = iscd_pa.origin_country and iscd_pa.DIM_OBJECT = 'PA'
          left join rms_p009qtzb_rms_ods.v_item_master im on isc.item=im.item 
where
isc.item not in (select item from rms_p009qtzb_rms_ods.v_uda_item_lov where uda_id = '9677' and uda_value = '1')
AND isc.item NOT IN (select distinct(item) from rms_p009qtzb_rms_ods.v_uda_item_date where uda_id = 6 and uda_date < current_date)
and isc.item not in (select distinct(item) from rms_p009qtzb_rms_ods.v_uda_item_lov where uda_id = 5 and uda_value in (6, 15, 14))
SQL;


$request = $dsn->prepare($sql);
$request->execute();
$result = $request->fetchAll(PDO::FETCH_ASSOC);

file_put_contents(FOLDER_ROOT.'files/import_sql/history_in/json_test.json', json_encode($result));
exec("date +%d.%m.%Y' '%H:%M", $output);
echo $output[0].' json_test.json в папку files/import_sql/history_in/json_test.json';
?>