function multiplicar(a) {
    return a * 1000;
}

function dividir(a) {
    return a / 1000;
}

function conseguir_valores(id,utilidad_r,utilidad_p,utilidad_d){

	//console.log($("#id").val(id));
	$("#id").val(id);
	$("#utilidad_r").val(utilidad_r);
	$("#utilidad_p").val(utilidad_p);
	$("#utilidad_d").val(utilidad_d);
}

function suma_adictivo(id, id2, primer_valor) {
    $(document).on("keyup", "#" + id, function() {

    	//console.log($("#id").val()+' '+$("#utilidad_r").val()+' '+$("#utilidad_p").val()+' '+$("#utilidad_d").val());
    	//alert($("#utilidad_r").val());
    	var primer_resultado = dividir( multiplicar($("#"+primer_valor).val()) * 0.16 ); 
    	var segundo_resultado = dividir( multiplicar(primer_resultado.toFixed(2)) + multiplicar($("#"+primer_valor).val()) ); 
    	var suma = dividir( multiplicar($("#" + id).val()) - multiplicar(segundo_resultado.toFixed(2)) );
    	//console.log(id);
        //var suma = dividir(multiplicar($("#" + id).val()) + multiplicar(primer_valor));
        if ($("#" + id).val() == '') {
        	$("#" + id2).val('0');
        } else {
        	$("#" + id2).val(suma);
        }
        
    });
}


function so_number(id,estacion){
    $("#input-estacion_id").val(id);
    $("#input-estacion").val(estacion);
}

function autorizar(id,estacion,order_id){
    $("#input-id").val(id);
    $("#input-estacion_name").val(estacion);
    $("#order_id").val(order_id);
}

function envio_emergencia(order_id){
    $("#order_id_e").val(order_id);
    //alert($("#order_id_e").val());
}