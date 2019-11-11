function _wcsk_delete(id) {
	if(confirm(wcsk_admin_notices.wcsk_delete_record)) {
		document.getElementById("_wpnonce").value = document.sa_wcsk_dashboard._wpnonce.value;
		document.sa_wcsk_dashboard.action="admin.php?page=woocommerce_serial_key&tab=dashboard&ac=del&did="+id;
		document.sa_wcsk_dashboard.submit();
	}
}

function _wcsk_bulkaction() {
	if (document.sa_wcsk_dashboard.bulk_action.value=="") {
		alert(wcsk_admin_notices.wcsk_bulk_action); 
		document.sa_wcsk_dashboard.bulk_action.focus();
		return false;
	}

	if (document.sa_wcsk_dashboard.bulk_action.value == "delete") {
		if (confirm(wcsk_admin_notices.wcsk_confirm_delete)) {
			document.getElementById("wcsk_dashboard").value = 'delete';
			document.getElementById("_wpnonce").value = document.sa_wcsk_dashboard._wpnonce.value;
			document.sa_wcsk_dashboard.action="admin.php?page=woocommerce_serial_key&tab=dashboard";
			document.sa_wcsk_dashboard.submit();
		} else {
			return false;
		}
	}
}