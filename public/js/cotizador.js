function multiplicar(a) {
    return a * 1000;
}

function dividir(a) {
    return a / 1000;
}
/*function suma_fits(id,id2, primer_valor, segundo_valor, tercer_valor){
	$(document).on("keyup","#"+id, function() {
		var suma = dividir(multiplicar($("#"+id).val()) + multiplicar(primer_valor) + multiplicar(segundo_valor) + multiplicar(tercer_valor));
		$("#"+id2).val(suma);
	});
}*/
function suma_adictivo(id, id2, primer_valor) {
    $(document).on("keyup", "#" + id, function() {
        var suma = dividir(multiplicar($("#" + id).val()) + multiplicar(primer_valor));
        $("#" + id2).val(suma);
    });
}

function suma_descuento(aditivo, id, id2, id3,nivel_inferior_r, nivel_superior_r, primer_valor,nivel_inferior_p, nivel_superior_p, segundo_valor,nivel_inferior_d, nivel_superior_d, tercer_valor, suma_fit, index, regular_fit, premium_fit, disel_fit, descuento_nivel_final_regular, descuento_nivel_final_premium, descuento_nivel_final_disel,precio_regular_pemex,precio_premium_pemex,precio_diesel_pemex ,descuentos_nivel_pemex_r,descuentos_nivel_pemex_p,descuentos_nivel_pemex_d) {

    var suma_R = dividir((multiplicar($("#"+id).val()) + multiplicar(descuento_nivel_final_regular) - multiplicar(primer_valor) + multiplicar(suma_fit)) - multiplicar(regular_fit));
    var suma_p = dividir((multiplicar($("#"+id2).val()) + multiplicar(descuento_nivel_final_premium) - multiplicar(segundo_valor) + multiplicar(suma_fit)) - multiplicar(premium_fit));
    var suma_D = dividir((multiplicar($("#"+id3).val()) + multiplicar(descuento_nivel_final_disel) - multiplicar(tercer_valor) + multiplicar(suma_fit)) - multiplicar(disel_fit));
    //console.log(precio_regular_pemex);
    var resta_pemex_r = dividir(multiplicar(precio_regular_pemex) - multiplicar(descuentos_nivel_pemex_r));
    var resta_pemex_p = dividir(multiplicar(precio_premium_pemex) - multiplicar(descuentos_nivel_pemex_p));
    var resta_pemex_d = dividir(multiplicar(precio_diesel_pemex) - multiplicar(descuentos_nivel_pemex_d));
    //console.log(resta_pemex_r);
    var icono_regular = '';
    var icono_premium = '';
    var icono_disel = '';

    if(suma_R <= resta_pemex_r){
        icono_regular = 'thumb_up_alt';
    } else {
        icono_regular = 'thumb_down_alt';
    }

    if(suma_p <= resta_pemex_p){
        icono_premium = 'thumb_up_alt';
    } else {
        icono_premium = 'thumb_down_alt';
    }

    if(suma_D <= resta_pemex_d){
        icono_disel = 'thumb_up_alt';
    } else {
        icono_disel = 'thumb_down_alt';
    }

    var htmlTags = '<tr class="text-center"><td>' + index + '</td><td style="background: linear-gradient(60deg, rgba(67, 160, 71, .2), rgba(67, 160, 71, .2));">'+ nivel_inferior_r +'</td><td style="background: linear-gradient(60deg, rgba(67, 160, 71, .2), rgba(67, 160, 71, .2));">'+ nivel_superior_r +'</td><td style="background: linear-gradient(60deg, rgba(67, 160, 71, .2), rgba(67, 160, 71, .2));"><i class="material-icons"> '+icono_regular+' </i></td><td style="background: linear-gradient(60deg, rgba(67, 160, 71, .2), rgba(67, 160, 71, .2));">' + suma_R + '</td><td style="background: linear-gradient(60deg, rgba(67, 160, 71, .2), rgba(67, 160, 71, .2));">' + resta_pemex_r + '</td><td style="background: linear-gradient(60deg, rgba(229, 57, 53, .2), rgba(229, 57, 53, .2));">'+ nivel_inferior_p +'</td><td style="background: linear-gradient(60deg, rgba(229, 57, 53, .2), rgba(229, 57, 53, .2));">'+ nivel_superior_p +'</td><td style="background: linear-gradient(60deg, rgba(229, 57, 53, .2), rgba(229, 57, 53, .2));"><i class="material-icons"> '+icono_premium+' </i></td><td style="background: linear-gradient(60deg, rgba(229, 57, 53, .2), rgba(229, 57, 53, .2));">' + suma_p + '</td><td style="background: linear-gradient(60deg, rgba(229, 57, 53, .2), rgba(229, 57, 53, .2));">'+ resta_pemex_p +'</td><td style="background: linear-gradient(60deg, rgba(33, 33, 33, .2), rgba(33, 33, 33, .2));">'+ nivel_inferior_d +'</td><td style="background: linear-gradient(60deg, rgba(33, 33, 33, .2), rgba(33, 33, 33, .2));">'+ nivel_superior_d +'</td><td style="background: linear-gradient(60deg, rgba(33, 33, 33, .2), rgba(33, 33, 33, .2));"><i class="material-icons"> '+icono_disel+' </i></td><td style="background: linear-gradient(60deg, rgba(33, 33, 33, .2), rgba(33, 33, 33, .2));">' + suma_D + '</td><td style="background: linear-gradient(60deg, rgba(33, 33, 33, .2), rgba(33, 33, 33, .2));">'+ resta_pemex_d +'</td></tr>';
    $('#' + aditivo + ' tbody').append(htmlTags);
}





 /*var suma_R = dividir(((multiplicar($("#" + id).val()) + multiplicar(suma_fit))) - multiplicar(regular_fit));
    var suma_p = dividir(((multiplicar($("#" + id2).val()) + multiplicar(suma_fit))) - multiplicar(premium_fit));
    var suma_D = dividir(((multiplicar($("#" + id3).val()) + multiplicar(suma_fit))) - multiplicar(disel_fit));

    var divicion_r = 0;
    var divicion_p = 0;
    var divicion_d = 0;

    if (primer_valor != 0 && segundo_valor != 0 && tercer_valor != 0) {
        divicion_r = suma_R / primer_valor;
        divicion_p = suma_p / segundo_valor;
        divicion_d = suma_D / tercer_valor;
    }

    suma_R = divicion_r + suma_R;
    suma_p = divicion_p + suma_p;
    suma_D = divicion_d + suma_D;*/