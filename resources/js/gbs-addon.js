/*** MailChimp API Key Edit Functions ***/

function editAPIKey()
{
	$("#gbs_mailchimp_api_key_new").removeAttr("disabled");
	$("#edit-api-key").hide();
	$("#validate-api-key").show();
	$("#mailchimp_settings_fields").fadeOut("slow");
//	$("#submit").hide();
}

$("#validate-api-key").live("click", function (e) {
	if ($("#gbs_mailchimp_api_key_new").val()) {
		var dataString = "api_key=" + $("#gbs_mailchimp_api_key_new").val() + "&validate=true";
		$.ajax({
			type: "POST",
			url: $tw_mc_mods_views_url + "validate-mailchimp-api-key.php",
			data: dataString,
			success: function (result) {
				if (result == "0") {
					$("#api-key-error").html("Invalid API Key");
				}
				else if (result == "2") {
					$("#api-key-error").html("API Key Already Present");
					$("#gbs_mailchimp_api_key_new").attr("disabled", true);
					$("#edit-api-key").show();
					$("#validate-api-key").hide();
					$("#mailchimp_settings_fields").fadeIn("slow");
				}
				else if (result == "1") {
					if ($("#mailchimp-api-key-exist").val() == 'true') {
						$("#api-key-error").html("API Key Validated. Pressing Save will delete all previous Settings related to MailChimp. Press Reset if you still want to use previous API Key.");
						$("#gbs_mailchimp_api_key_new").attr("disabled", true);
						$("#validate-api-key").hide();
						$("#save-api-key").show();
						$("#reset-api-key").show();
					}
					else {
						$("#api-key-error").html("API Key Validated. Press Save to use this Key.");
						$("#gbs_mailchimp_api_key_new").attr("disabled", true);
						$("#validate-api-key").hide();
						$("#save-api-key").show();
						$("#reset-api-key").show();
					}
				}
				else {
					$("#api-key-error").html("Unknown Error");
				}
			}
		});
		return true;
	}
	else {
		$("#api-key-error").html("Please provide Valid API Key");
	}
	return false;
});

$("#reset-api-key").live("click", function (e) {
	window.location = window.location;
});


$("#save-api-key").live("click", function (e) {
	if ($("#gbs_mailchimp_api_key_new").val()) {
		var dataString = "api_key=" + $("#gbs_mailchimp_api_key_new").val();
		$.ajax({
			type: "POST",
			url: $tw_mc_mods_views_url + "save-mailchimp-api-key.php",
			data: dataString,
			success: function (result) {
				if (result == "0") {
					$("#api-key-error").html("Unknown Error API Key");
				}
				else {
					$("#api-key-error").html("API Key Saved");
					window.location = window.location;
				}
			}
		});
		return true;
	}
	else {
		$("#api-key-error").html("Please provide Valid API Key");
	}
	return false;
});

/*** MailChimp Top Purchase Group Add Functions ***/

function showMailChimpTopPurchaseGroupAddChilds(id)
{
	if (id) {
		if(document.getElementById("gbs_mailchimp_top_purchase_group_add_id").value!="") {
			var gbs_mailchimp_top_purchase_group_add_id = document.getElementById("gbs_mailchimp_top_purchase_group_add_id").value;
			document.getElementById("gbs_mailchimp_top_purchase_group_add_bit_"+gbs_mailchimp_top_purchase_group_add_id).style.display="none";
		}
		var gbs_mailchimp_top_purchase_group_add_id = id;
		document.getElementById("gbs_mailchimp_top_purchase_group_add_id").value = gbs_mailchimp_top_purchase_group_add_id;
		document.getElementById("gbs_mailchimp_top_purchase_group_add_bit_"+gbs_mailchimp_top_purchase_group_add_id).style.display="block";
		document.getElementById("gbs_mailchimp_top_purchase_group_add_bit").value = document.getElementById("gbs_mailchimp_top_purchase_group_add_bit_"+gbs_mailchimp_top_purchase_group_add_id).value;
	}
}

function getMailChimpTopPurchaseGroupAddChildBit(id)
{
	document.getElementById("gbs_mailchimp_top_purchase_group_add_bit").value = id;
}

/*** MailChimp Top Purchase Group Remove Functions ***/

function showMailChimpTopPurchaseGroupRemoveChilds(id)
{
	if (id) {
		if(document.getElementById("gbs_mailchimp_top_purchase_group_remove_id").value!="") {
			var gbs_mailchimp_top_purchase_group_remove_id = document.getElementById("gbs_mailchimp_top_purchase_group_remove_id").value;
			document.getElementById("gbs_mailchimp_top_purchase_group_remove_bit_"+gbs_mailchimp_top_purchase_group_remove_id).style.display="none";
		}
		var gbs_mailchimp_top_purchase_group_remove_id = id;
		document.getElementById("gbs_mailchimp_top_purchase_group_remove_id").value = gbs_mailchimp_top_purchase_group_remove_id;
		document.getElementById("gbs_mailchimp_top_purchase_group_remove_bit_"+gbs_mailchimp_top_purchase_group_remove_id).style.display="block";
		document.getElementById("gbs_mailchimp_top_purchase_group_remove_bit").value = document.getElementById("gbs_mailchimp_top_purchase_group_remove_bit_"+gbs_mailchimp_top_purchase_group_remove_id).value;
	}
}

function getMailChimpTopPurchaseGroupRemoveChildBit(id)
{
	document.getElementById("gbs_mailchimp_top_purchase_group_remove_bit").value = id;
}

/*** MailChimp Non Renewed Group Functions ***/

function showMailChimpNonRenewedGroupChilds(id)
{
	if (id) {
		if(document.getElementById("gbs_mailchimp_non_renewed_group_id").value!="") {
			var gbs_mailchimp_non_renewed_group_id = document.getElementById("gbs_mailchimp_non_renewed_group_id").value;
			document.getElementById("gbs_mailchimp_non_renewed_group_bit_"+gbs_mailchimp_non_renewed_group_id).style.display="none";
		}
		var gbs_mailchimp_non_renewed_group_id = id;
		document.getElementById("gbs_mailchimp_non_renewed_group_id").value = gbs_mailchimp_non_renewed_group_id;
		document.getElementById("gbs_mailchimp_non_renewed_group_bit_"+gbs_mailchimp_non_renewed_group_id).style.display="block";
		document.getElementById("gbs_mailchimp_non_renewed_group_bit").value = document.getElementById("gbs_mailchimp_non_renewed_group_bit_"+gbs_mailchimp_non_renewed_group_id).value;
	}
}

function getMailChimpNonRenewedGroupChildBit(id)
{
	document.getElementById("gbs_mailchimp_non_renewed_group_bit").value = id;
}

/*** Top Product Purchase Trigger Functions ***/

function addTopProductPurchaseTrigger(id)
{
	var seconds = new Date().getTime() / 1000;
	document.getElementById("gbs_top_purchase_deal_timestamp_"+id).value = Math.round(seconds);
//	contentID.removeChild(document.getElementById("strPurchaseProduct"+id));
//	document.getElementById("gbs_top_purchase_deal_timestamp").value = Math.round(seconds);
/*
	if (document.getElementById("gbs_top_purchase_deal_selected").value == "true") {
		if (id == document.getElementById("gbs_top_purchase_last_deal").value) {
			var contentID = document.getElementById("topProducts");
			contentID.parentNode.removeChild(contentID);
			document.getElementById("gbs_top_purchase_deal_selected").value = "false";
		}
		else {
			var contentID = document.getElementById("topProducts");
			document.getElementById("gbs_top_purchase_deal_id").value = id;
			document.getElementById("gbs_top_purchase_deal_timestamp").value = time;
		}
	}
	else {
		var contentID = document.getElementById("top_templates");
		var newTBDiv = document.createElement("div");
		newTBDiv.setAttribute("id", "topProducts");
		var fields = "";
		fields = "<input type='hidden' id='gbs_top_purchase_deal_id' name='gbs_top_purchase_deal_id[]' value='"+id+"'> ";
		fields += "<input type='hidden' id='gbs_top_purchase_deal_timestamp' name='gbs_top_purchase_deal_timestamp[]' value='"+time+"'> ";
		fields += "</div>";
		newTBDiv.innerHTML = fields;
		contentID.appendChild(newTBDiv);
		document.getElementById("gbs_top_purchase_deal_selected").value = "true";
	}
*/
}

/*** Scheduled Template Functions ***/

function addElement()
{
	intTextBox = parseInt(document.getElementById("scheduled_template_count").value);
	var contentID = document.getElementById("scheduled_templates");
	var newTBDiv = document.createElement("div");
	newTBDiv.setAttribute("id", "strTemplate"+intTextBox);
	var selectBox = "";
	selectBox = "<div class='form-tab'>";
	selectBox += "<span class='label'>Scheduled Template</span> ";
	selectBox += "<select id='gbs_mailchimp_scheduled_template' name='gbs_mailchimp_scheduled_template[]'>";
	$("select#gbs_mailchimp_scheduled_templates").find("option").each(function() {
		selectBox += "<option value='"+$(this).val()+"'>"+$(this).text()+"</option>";
	});
	selectBox +="</select> ";
	selectBox +="<div class='form-tab-2'>";
	selectBox += "<span class='label-inner'>Delay</span> ";
	selectBox += "<input type='text' name='gbs_mailchimp_scheduled_delay[]' class='numeric-field' maxlength='5' value='10'> ";
	selectBox += "<span class='label-inner'>Subject</span> ";
	selectBox += "<input type='text' name='gbs_mailchimp_scheduled_template_subject[]' value='&nbsp;'> ";
	selectBox += "<a href='javascript:removeTemplateElement("+intTextBox+");' id='submit' class='button button-primary' >Remove</a>";
	selectBox += "</div>";
	selectBox += "</div>";
	newTBDiv.innerHTML = selectBox;
	contentID.appendChild(newTBDiv);
	document.getElementById("scheduled_template_count").value = intTextBox + 1;
}

function removeTemplateElement(id)
{
	if(id != 0)
	{
		var contentID = document.getElementById("scheduled_templates");
		contentID.removeChild(document.getElementById("strTemplate"+id));
	}
}

/*** Scheduled Product Purchase Trigger Functions ***/

function addPurchaseProduct()
{
	var seconds = new Date().getTime() / 1000;
	intTextBox = parseInt(document.getElementById("top_purchase_deal_count").value) + 1;
	var contentID = document.getElementById("top_product_purchase_trigger");
	var newTBDiv = document.createElement("div");
	newTBDiv.setAttribute("id", "strPurchaseProduct"+intTextBox);
	var selectBox = "";
	selectBox = "<div class='form-tab'>";
	selectBox += "<span class='label'>Top Product Purchase Trigger</span> ";
	selectBox += "<select id='gbs_top_purchase_deal_id_"+intTextBox+"' name='gbs_top_purchase_deal_id[]' onChange='addTopProductPurchaseTrigger("+intTextBox+");'>";
	$("select#gbs_top_purchase_deals").find("option").each(function() {
		selectBox += "<option value='"+$(this).val()+"'>"+$(this).text()+"</option>";
	});
	selectBox +="</select> ";
	selectBox += "<input type='hidden' id='gbs_top_purchase_deal_timestamp_"+intTextBox+"' name='gbs_top_purchase_deal_timestamp[]' value='"+Math.round(seconds)+"'> ";
	selectBox += "<a href='javascript:removePurchaseProduct("+intTextBox+");' id='submit' class='button button-primary' >Remove</a>";
	selectBox += "</div>";
	newTBDiv.innerHTML = selectBox;
	contentID.appendChild(newTBDiv);
	document.getElementById("top_purchase_deal_count").value = intTextBox;
}

function removePurchaseProduct(id)
{
//	if(id != 0)
//	{
		var contentID = document.getElementById("top_product_purchase_trigger");
		contentID.removeChild(document.getElementById("strPurchaseProduct"+id));
//	}
}

/*** MailChimp Formulate Groups Functions ***/

function getMailChimpGroups(id)
{
	var mailChimpKey = $("#gbs_mailchimp_api_key_new").val();
	var dataString = "list_id=" + id + "&mailChimpKey="+ mailChimpKey;
	$.ajax({
		type: "POST",
		url: $tw_mc_mods_views_url + "list-of-top-purchase-add-groups.php",
		data: dataString,
		success: function (result) {
			//alert(result);
			$("#gbs_mailchimp_top_purchase_group_add_parents").remove();
			var topgroupID = $("#gbs_mailchimp_top_purchase_group_add_id").val();
			if(topgroupID!= null) {
				$("#"+topgroupID).remove();
				$("#gbs_mailchimp_top_purchase_group_add_bit_"+topgroupID).remove();
			}
			$("#gbs_mailchimp_top_purchase_group_add_id").val('');
			$("#gbs_mailchimp_top_purchase_group_add_bit").val('');
			$("#top_purchase_group_add_grouping").html(result);
		}
	});
	$.ajax({
		type: "POST",
		url: $tw_mc_mods_views_url + "list-of-top-purchase-remove-groups.php",
		data: dataString,
		success: function (result) {
			//alert(result);
			$("#gbs_mailchimp_top_purchase_group_remove_parents").remove();
			var topgroupID = $("#gbs_mailchimp_top_purchase_group_remove_id").val();
			if(topgroupID!= null) {
				$("#"+topgroupID).remove();
				$("#gbs_mailchimp_top_purchase_group_remove_bit_"+topgroupID).remove();
			}
			$("#gbs_mailchimp_top_purchase_group_remove_id").val('');
			$("#gbs_mailchimp_top_purchase_group_remove_bit").val('');
			$("#top_purchase_group_remove_grouping").html(result);
		}
	});
	$.ajax({
		type: "POST",
		url: $tw_mc_mods_views_url + "list-of-non-renewed-groups.php",
		data: dataString,
		success: function (result) {
			$("#gbs_mailchimp_non_renewed_group_parents").remove();
			var nonrenewedgroupID = $("#gbs_mailchimp_non_renewed_group_id").val();
			if(nonrenewedgroupID!= null) {
				$("#"+nonrenewedgroupID).remove();
				$("#gbs_mailchimp_non_renewed_group_bit_"+nonrenewedgroupID).remove();
			}
			$("#gbs_mailchimp_non_renewed_group_id").val('');
			$("#gbs_mailchimp_non_renewed_group_bit").val('');
			$("#non_renewed_group_grouping").html(result);
		}
	});
}

function funcBecomeTop()
{
	var dataString = "name=" + $("#firstname").val() + "&email="+ $("#primaryemail").val();
	$.ajax({
		type: "POST",
		url: $tw_mc_mods_views_url + "become-top.php",
		data: dataString,
		success: function (result) {
			if (result == "0") {
				alert ("Invalid Value for Name or Email");
				return false;
			}
			if (result == "2") {
				alert ("Error in Saving Record");
				return false;
			}
			else {
				alert ("Record Saved");
				return true;
			}
		}
	});
	return false;
}
