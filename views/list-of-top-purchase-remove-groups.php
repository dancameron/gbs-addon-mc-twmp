<?php 
	$list_id = $_POST['list_id'];
	$mailchimpKey = $_POST['mailChimpKey'];
	// check list id is empty or not 
	if($list_id)
	{
		// include mail chimp class
		include_once ('../lib/MCAPI.class.php');
		$api = new MCAPI($mailchimpKey);
		$retval = $api->listInterestGroupings($list_id);
		
		if((!empty($retval)) && $retval!='' && $retval!=NULL)
		{
			$topGroup = '<span style="display:inline-block;">';
			$topGroup .= '<div id="top_purchase_group_remove_grouping"></div>';
			$topGroup .= '<select onchange="showMailChimpTopPurchaseGroupRemoveChilds(this.value);" name="gbs_mailchimp_top_purchase_group_remove_parents" id="gbs_mailchimp_top_purchase_group_remove_parents">';
			$topGroup .= '<option value="">-- SELECT --</option>';
			foreach ($retval as $key=>$value) 
			{
				$topGroup .= '<option value="'.$value['id'].'">'.$value['name'].'</option>';
			}
			$topGroup .= '</select>';
			$topGroup .= '</span>';
			// getting sub-groups
			foreach($retval as $key=>$value)
			{
				$topGroup .= '<span style="display:inline-block;">';
				$topGroup .= '<select onchange="getMailChimpTopPurchaseGroupRemoveChildBit(this.value);" style="display:none" name="gbs_mailchimp_top_purchase_group_remove_bit_'.$value['id'].'" class="mail-chimp-top-group-select" id="gbs_mailchimp_top_purchase_group_remove_bit_'.$value['id'].'">';
				foreach ($value['groups'] as $k=>$v) {
					$topGroup .= '<option value="'.$v['bit'].'">'.$v['name'].'</option>';
				}
				$topGroup .= '</select>';
				$topGroup .= '</span>';
			}
			echo $topGroup;
		}
		else {
			$topGroup = '<select id="gbs_mailchimp_top_purchase_group_remove_parents" name="gbs_mailchimp_top_purchase_group_remove_parents">';
			$topGroup .= '<option value="">-- SELECT --</option>';
			$topGroup .= '</select>';
			echo $topGroup;
		}
	}
	else
	{
		$topGroup = '<select id="gbs_mailchimp_top_purchase_group_remove_parents" name="gbs_mailchimp_top_purchase_group_remove_parents">';
		$topGroup .= '<option value="">-- SELECT --</option>';
		$topGroup .= '</select>';
		echo $topGroup;
	}
?>
