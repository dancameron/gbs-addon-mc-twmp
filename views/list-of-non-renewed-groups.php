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
			$nonRenewedGroup = '<span style="display:inline-block;">';
			$nonRenewedGroup .= '<div id="non_renewed_group_grouping"></div>';
			$nonRenewedGroup .= '<select onchange="showMailChimpNonRenewedGroupChilds(this.value);" name="gbs_mailchimp_non_renewed_group_parents" id="gbs_mailchimp_non_renewed_group_parents">';
			$nonRenewedGroup .= '<option value="">-- SELECT --</option>';
			foreach ($retval as $key=>$value) 
			{
				$nonRenewedGroup .= '<option value="'.$value['id'].'">'.$value['name'].'</option>';
			}
			$nonRenewedGroup .= '</select>';
			$nonRenewedGroup .= '</span>';
			// getting sub-groups
			foreach($retval as $key=>$value)
			{
				$nonRenewedGroup .= '<span style="display:inline-block;">';
				$nonRenewedGroup .= '<select onchange="getMailChimpNonRenewedGroupChildBit(this.value);" style="display:none" name="gbs_mailchimp_non_renewed_group_bit_'.$value['id'].'" class="mail-chimp-non-renewed-group-select" id="gbs_mailchimp_non_renewed_group_bit_'.$value['id'].'">';
				foreach ($value['groups'] as $k=>$v) {
					$nonRenewedGroup .= '<option value="'.$v['bit'].'">'.$v['name'].'</option>';
				}
				$nonRenewedGroup .= '</select>';
				$nonRenewedGroup .= '</span>';
			}
			echo $nonRenewedGroup;
		}
		else {
			$nonRenewedGroup = '<select id="gbs_mailchimp_non_renewed_group_parents" name="gbs_mailchimp_non_renewed_group_parents" onChange="getGrouping(this.value);">';
			$nonRenewedGroup .= '<option value="">-- SELECT --</option>';
			$nonRenewedGroup .= '</select>';
			echo $nonRenewedGroup;
		}
	}
	else
	{
		$nonRenewedGroup = '<select id="gbs_mailchimp_non_renewed_group_parents" name="gbs_mailchimp_non_renewed_group_parents" onChange="getGrouping(this.value);">';
		$nonRenewedGroup .= '<option value="">-- SELECT --</option>';
		$nonRenewedGroup .= '</select>';
		echo $nonRenewedGroup;
	}
?>
