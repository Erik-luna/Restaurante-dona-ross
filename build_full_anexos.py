# -*- coding: utf-8 -*-
"""
Script maestro para generar el documento de Anexos del Proyecto de Grado:
"Sistema web de ventas, control de stock y pedidos en línea, caso Restaurante Doña Ross"
Instituto Tecnológico Bolivia Mar - El Alto, Bolivia - 2026
Postulante: Erik Jesus Saavedra Luna
"""

import os
import re
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_COLOR_INDEX
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn

from docx_helpers import (
    set_cell_margins, set_cell_shading, set_table_borders,
    add_styled_text, create_table_custom
)

def build_anexos_document():
    doc = docx.Document()

    # Configuración de márgenes estándar de grado (Izquierdo 3.0 cm, Superior/Derecho/Inferior 2.5 cm)
    for section in doc.sections:
        section.top_margin = Inches(0.98)     # ~2.5 cm
        section.bottom_margin = Inches(0.98)  # ~2.5 cm
        section.left_margin = Inches(1.18)    # ~3.0 cm
        section.right_margin = Inches(0.98)   # ~2.5 cm
        section.different_first_page_header_footer = True
        
        # Encabezado para páginas siguientes
        header = section.header
        p_hdr = header.paragraphs[0]
        p_hdr.alignment = WD_ALIGN_PARAGRAPH.RIGHT
        p_hdr.paragraph_format.space_after = Pt(4)
        run_h = p_hdr.add_run("ITBM • CARRERA DE SISTEMAS INFORMÁTICOS | ANEXOS DEL PROYECTO DE GRADO")
        run_h.font.name = "Arial"
        run_h.font.size = Pt(8.5)
        run_h.font.color.rgb = RGBColor(120, 120, 120)

        # Pie de página
        footer = section.footer
        p_ftr = footer.paragraphs[0]
        p_ftr.alignment = WD_ALIGN_PARAGRAPH.CENTER
        run_f = p_ftr.add_run("Restaurante “Doña Ross” — Sistema Web de Ventas, Control de Stock y Pedidos en Línea (2026)")
        run_f.font.name = "Arial"
        run_f.font.size = Pt(8.5)
        run_f.font.color.rgb = RGBColor(120, 120, 120)

    # Estilo base
    style_normal = doc.styles['Normal']
    style_normal.font.name = 'Arial'
    style_normal.font.size = Pt(11)
    style_normal.font.color.rgb = RGBColor(30, 30, 30)

    # Funciones auxiliares locales
    def add_title(text, size=15, bold=True, space_before=12, space_after=6, align=WD_ALIGN_PARAGRAPH.CENTER, color=RGBColor(20, 20, 20)):
        p = doc.add_paragraph()
        p.alignment = align
        p.paragraph_format.space_before = Pt(space_before)
        p.paragraph_format.space_after = Pt(space_after)
        p.paragraph_format.line_spacing = 1.15
        p.paragraph_format.keep_with_next = True
        run = p.add_run(text)
        run.font.name = 'Arial'
        run.font.size = Pt(size)
        run.bold = bold
        run.font.color.rgb = color
        return p

    def add_h1(text):
        return add_title(text, size=13.5, bold=True, space_before=16, space_after=6, align=WD_ALIGN_PARAGRAPH.LEFT, color=RGBColor(11, 46, 89))

    def add_h2(text):
        return add_title(text, size=12, bold=True, space_before=12, space_after=4, align=WD_ALIGN_PARAGRAPH.LEFT, color=RGBColor(31, 78, 121))

    def add_h3(text):
        return add_title(text, size=11, bold=True, space_before=8, space_after=3, align=WD_ALIGN_PARAGRAPH.LEFT, color=RGBColor(45, 62, 80))

    def add_p(text="", space_before=0, space_after=6, line_spacing=1.15, align=WD_ALIGN_PARAGRAPH.JUSTIFY, bold=False, italic=False):
        p = doc.add_paragraph()
        p.alignment = align
        p.paragraph_format.space_before = Pt(space_before)
        p.paragraph_format.space_after = Pt(space_after)
        p.paragraph_format.line_spacing = line_spacing
        if text:
            add_styled_text(p, text, default_font_size=11, default_bold=bold, default_italic=italic)
        return p

    def add_bullet(text, bold_prefix=None):
        p = doc.add_paragraph(style='List Bullet')
        p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.line_spacing = 1.15
        if bold_prefix:
            run_b = p.add_run(bold_prefix)
            run_b.font.name = 'Arial'
            run_b.font.size = Pt(11)
            run_b.bold = True
        add_styled_text(p, text, default_font_size=11)
        return p

    def add_screenshot_box(label):
        """Agrega un recuadro formal con espacio para pegar captura de pantalla"""
        tbl = doc.add_table(rows=1, cols=1)
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        cell = tbl.cell(0, 0)
        cell.width = Inches(6.0)
        set_cell_margins(cell, top=260, bottom=260, left=200, right=200)
        set_cell_shading(cell, "FBFBFB")
        
        # Borde punteado/simple para área de captura
        tcPr = cell._tc.get_or_add_tcPr()
        borders = parse_xml(f'''
            <w:tcBorders {nsdecls("w")}>
                <w:top w:val="dashed" w:sz="6" w:space="0" w:color="A5A5A5"/>
                <w:left w:val="dashed" w:sz="6" w:space="0" w:color="A5A5A5"/>
                <w:bottom w:val="dashed" w:sz="6" w:space="0" w:color="A5A5A5"/>
                <w:right w:val="dashed" w:sz="6" w:space="0" w:color="A5A5A5"/>
            </w:tcBorders>
        ''')
        tcPr.append(borders)
        
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(0)
        add_styled_text(p, label, default_font_size=10, default_bold=True)
        
        p_space = doc.add_paragraph()
        p_space.paragraph_format.space_before = Pt(0)
        p_space.paragraph_format.space_after = Pt(6)

    print("Framework configurado, generando portada...")

    # ==========================================
    # 1. PORTADA
    # ==========================================
    doc.add_paragraph() # Espacio inicial
    add_title("INSTITUTO TECNOLÓGICO BOLIVIA MAR", size=15, bold=True, space_before=10, space_after=3)
    add_title("CARRERA DE SISTEMAS INFORMÁTICOS", size=13, bold=True, space_before=0, space_after=24)

    add_title("PROYECTO DE GRADO", size=14, bold=True, space_before=20, space_after=12)
    add_title("SISTEMA WEB DE VENTAS, CONTROL DE STOCK Y PEDIDOS EN LÍNEA\nCASO: RESTAURANTE “DOÑA ROSS”", size=16, bold=True, space_before=0, space_after=24, color=RGBColor(11, 46, 89))

    add_title("DOCUMENTO COMPLEMENTARIO DE ANEXOS\n(ANEXOS D A L)", size=13, bold=True, space_before=0, space_after=36, color=RGBColor(60, 60, 60))

    add_p("PARA OPTAR AL TÍTULO DE TÉCNICO SUPERIOR EN SISTEMAS INFORMÁTICOS", space_before=10, space_after=28, align=WD_ALIGN_PARAGRAPH.CENTER, bold=True)

    add_p("POSTULANTE: ERIK JESUS SAAVEDRA LUNA", space_before=4, space_after=4, align=WD_ALIGN_PARAGRAPH.CENTER, bold=True)
    add_p("TUTOR METODOLÓGICO: M.Sc. RICHARD JHOSEPES TACACHIRA PÉREZ", space_before=2, space_after=4, align=WD_ALIGN_PARAGRAPH.CENTER, bold=True)
    add_p("TUTOR TÉCNICO: Lic. HILDA CALLISAYA APAZA", space_before=2, space_after=36, align=WD_ALIGN_PARAGRAPH.CENTER, bold=True)

    add_title("EL ALTO - BOLIVIA\n2026", size=12, bold=True, space_before=30, space_after=0)

    doc.add_page_break()

    # ==========================================
    # 2. ÍNDICE DE ANEXOS
    # ==========================================
    add_title("ÍNDICE DE ANEXOS", size=16, bold=True, space_before=12, space_after=16, align=WD_ALIGN_PARAGRAPH.CENTER)

    indice_data = [
        ("ANEXO D", "Guía de entrevista estructurada aplicada a los propietarios del Restaurante “Doña Ross”"),
        ("ANEXO E", "Ficha técnica de observación de campo de procesos operativos y tiempos de atención"),
        ("ANEXO F", "Matriz de análisis documental de soportes físicos y canales de comunicación informales"),
        ("ANEXO G", "Cuestionario de usabilidad bajo la norma ISO/IEC 25010 (Escala Likert de 1 a 5) y matriz de resultados"),
        ("ANEXO H", "Registro de mediciones técnicas: rendimiento sobre la infraestructura en la nube Railway y métricas de volumen de código fuente con la herramienta CLOC"),
        ("ANEXO I", "Manual de usuario integral por rol (Cliente, Administrador, Cajero y Cocina)"),
        ("ANEXO J", "Manual técnico de configuración, instalación local en XAMPP, despliegue en Railway y gestión de copias de seguridad"),
        ("ANEXO K", "Acta institucional de entrega del sistema informático y conformidad de capacitación del personal"),
        ("ANEXO L", "Carta formal de aceptación y satisfacción del sistema emitida por la gerencia del restaurante")
    ]

    for sigla, desc in indice_data:
        p_ind = doc.add_paragraph()
        p_ind.paragraph_format.space_before = Pt(3)
        p_ind.paragraph_format.space_after = Pt(5)
        p_ind.paragraph_format.line_spacing = 1.15
        run_s = p_ind.add_run(f"{sigla}: ")
        run_s.font.name = "Arial"
        run_s.font.size = Pt(11)
        run_s.bold = True
        run_s.font.color.rgb = RGBColor(11, 46, 89)
        run_d = p_ind.add_run(desc)
        run_d.font.name = "Arial"
        run_d.font.size = Pt(11)

    add_p("Nota de estructuración: Los Anexos A (Árbol del problema), B (Árbol de objetivos) y C (Cronograma de desarrollo del proyecto) se encuentran incorporados en el cuerpo principal del documento de grado (páginas 129 a 131). El presente volumen reúne los instrumentos metodológicos, pruebas técnicas, manuales operativos y actas de transferencia correspondientes a los Anexos D al L.", space_before=20, space_after=10, italic=True)

    doc.add_page_break()

    # ==========================================
    # ANEXO D
    # ==========================================
    print("Generando Anexo D...")
    add_h1("ANEXO D: GUÍA DE ENTREVISTA ESTRUCTURADA A LOS PROPIETARIOS")

    add_p("El presente instrumento metodológico corresponde a la guía de entrevista estructurada aplicada a los propietarios y administradores del Restaurante “Doña Ross”, la señora Gabriela Peralta Limachi y el señor Marco García Mamani, con la finalidad de recopilar de manera directa y sistemática los requerimientos funcionales y no funcionales que guiaron el diseño del sistema web bajo la metodología UWE (UML-Based Web Engineering).")

    add_h2("D.1. Ficha técnica de la entrevista")

    ficha_d_headers = ["Parámetro", "Detalle Institucional"]
    ficha_d_rows = [
        ["Institución académica", "Instituto Tecnológico Bolivia Mar (Carrera de Sistemas Informáticos)"],
        ["Establecimiento beneficiario", "Restaurante “Doña Ross” (NIT 6060991015, Régimen Tributario Simplificado)"],
        ["Ubicación del establecimiento", "Zona Ciudad Satélite, ciudad de El Alto, departamento de La Paz - Bolivia"],
        ["Entrevistados", "Sra. Gabriela Peralta Limachi (Propietaria / Gestión General)\nSr. Marco García Mamani (Propietario / Encargado de Caja)"],
        ["Entrevistador / Postulante", "Erik Jesus Saavedra Luna"],
        ["Tutor metodológico", "M.Sc. Richard Jhosepes Tacachira Pérez"],
        ["Tutora técnica", "Lic. Hilda Callisaya Apaza"],
        ["Fecha de realización", "[COMPLETAR: Fecha de aplicación de la entrevista, ej. 15 de mayo de 2026]"],
        ["Hora de inicio y conclusión", "[COMPLETAR: Horario exacto, ej. 15:30 a 17:15]"],
        ["Lugar de la sesión", "Instalaciones del Restaurante “Doña Ross”, Ciudad Satélite, El Alto"],
        ["Objetivo de la entrevista", "Diagnosticar los flujos actuales de información manual en ventas, control de porciones de platos, cuadres de caja y pedidos por delivery, así como consensuar las especificaciones del nuevo sistema web."]
    ]

    create_table_custom(doc, ficha_d_headers, ficha_d_rows, 
                        col_widths=[Inches(2.2), Inches(4.3)], 
                        table_title="Tabla 36: Ficha técnica de la entrevista a los propietarios", 
                        table_note="Nota. Elaboración propia (2026).")

    add_h2("D.2. Cuestionario temático estructurado")
    add_p("A continuación, se detalla el conjunto de preguntas formuladas durante la sesión de levantamiento de requisitos, clasificadas por áreas operativas y módulos funcionales del negocio:")

    preguntas_d = [
        ("Bloque 1: Gestión de ventas presenciales en salón", [
            ("1.1", "¿Cómo se registran actualmente los pedidos de los comensales que acuden a consumir al salón del restaurante?", "[COMPLETAR: Respuesta detallada sobre uso de talonarios de papel y notas manuales por los meseros]"),
            ("1.2", "¿Qué dificultades se presentan con mayor frecuencia al momento de trasladar las comandas físicas a la cocina?", "[COMPLETAR: Respuesta sobre letra ilegible, pérdidas de papel, duplicidad y retrasos en horas pico]"),
            ("1.3", "¿Cómo se efectúa el cálculo del monto total a pagar por los clientes y qué comprobante se les entrega?", "[COMPLETAR: Respuesta sobre cálculo mental o calculadora y entrega de nota de venta informal]"),
            ("1.4", "¿Qué modalidades de pago aceptan en el salón y cómo verifican las transferencias electrónicas por QR?", "[COMPLETAR: Respuesta sobre pago en efectivo y revisión manual de capturas QR en el celular de los dueños]"),
            ("1.5", "¿Qué información necesitan visualizar de forma inmediata para agilizar el cobro en horas de alta concurrencia?", "[COMPLETAR: Respuesta sobre lista de platos con precios actualizados y cálculo automático del cambio]")
        ]),
        ("Bloque 2: Control de existencias y stock de platos preparados", [
            ("2.1", "¿Cómo estiman y controlan diariamente la cantidad de porciones preparadas de cada plato tradicional (silpancho, fricasé, pique macho, ranga, brazuelo)?", "[COMPLETAR: Respuesta sobre conteo empírico en ollas y recipientes al inicio del servicio]"),
            ("2.2", "¿Qué sucede cuando un comensal solicita un plato que ya se ha terminado en la cocina?", "[COMPLETAR: Respuesta sobre malestar del cliente, demoras al tener que volver a tomar el pedido y pérdidas]"),
            ("2.3", "Durante la jornada de almuerzo, ¿se preparan porciones adicionales (reposiciones) y cómo se comunican a la atención?", "[COMPLETAR: Respuesta sobre avisos verbales entre cocineros y personal de salón]"),
            ("2.4", "¿Cómo se gestionan las porciones que no pueden venderse por sobrecocción, accidentes o deterioro (mermas)?", "[COMPLETAR: Respuesta sobre descarte sin registro formal del motivo ni cuantificación económica]"),
            ("2.5", "¿Consideran indispensable que el sistema descuente automáticamente las porciones y bloquee platos agotados?", "[COMPLETAR: Respuesta sobre necesidad absoluta de bloqueo automático para evitar ofrecer platos sin stock]")
        ]),
        ("Bloque 3: Gestión de caja, arqueo y control de ingresos", [
            ("3.1", "¿Con qué monto de dinero en efectivo (fondo inicial) inician habitualmente la jornada laboral?", "[COMPLETAR: Respuesta sobre monto inicial asignado para dar cambio a los comensales]"),
            ("3.2", "¿Cómo registran las salidas imprevistas de dinero en efectivo durante el turno (compras de urgencia, pan, hielo)?", "[COMPLETAR: Respuesta sobre notas en papelitos sueltos guardados dentro del cajón de dinero]"),
            ("3.3", "¿Qué procedimiento siguen al finalizar la jornada laboral para comprobar si el dinero recaudado coincide con las ventas?", "[COMPLETAR: Respuesta sobre conteo físico de billetes y monedas y comparación contra sumas en cuaderno]"),
            ("3.4", "¿Con qué frecuencia se presentan diferencias (faltantes o sobrantes de dinero) y a qué causas las atribuyen?", "[COMPLETAR: Respuesta sobre equivocaciones en dar cambio y omisión involuntaria de notas de venta]"),
            ("3.5", "¿Qué datos esperan obtener en el resumen final de cierre de caja para asegurar la transparencia financiera?", "[COMPLETAR: Respuesta sobre desglose de ventas en salón, cobros de delivery, entradas, salidas y diferencia auditada]")
        ]),
        ("Bloque 4: Servicio de entrega a domicilio (Delivery)", [
            ("4.1", "¿Qué zonas de la ciudad de El Alto cubren actualmente con el servicio de entrega a domicilio?", "[COMPLETAR: Respuesta sobre cobertura prioritaria en Ciudad Satélite, Santa Rosa, Villa Dolores y zonas aledañas]"),
            ("4.2", "¿Qué información mínima consideran obligatoria solicitar a un cliente para despachar un pedido a domicilio?", "[COMPLETAR: Respuesta sobre nombre completo, número de teléfono/WhatsApp, dirección exacta y referencias claras]"),
            ("4.3", "¿Cómo calculan y coordinan el costo del servicio de envío hacia el domicilio del cliente?", "[COMPLETAR: Respuesta sobre tarifa fija o variable coordinada de forma directa con el repartidor]"),
            ("4.4", "¿Qué comprobante o constancia de compra requiere el cliente para verificar los platillos solicitados y el monto a pagar?", "[COMPLETAR: Respuesta sobre nota de venta digital con detalle de platillos, precios y costo total]"),
            ("4.5", "¿Cómo controlan que el dinero cobrado por el repartidor ingrese efectivamente a la caja del restaurante?", "[COMPLETAR: Respuesta sobre rendición de cuentas al retornar del despacho y confirmación en caja]")
        ]),
        ("Bloque 5: Problemática de los canales informales (WhatsApp y llamadas)", [
            ("5.1", "¿Cuánto tiempo invierte el personal en atender un pedido por WhatsApp en horas pico de almuerzo?", "[COMPLETAR: Respuesta sobre demora de 3 a 5 minutos por cliente enviando fotos del menú y escribiendo precios]"),
            ("5.2", "¿Qué problemas surgen al transcribir manualmente los datos de entrega desde las conversaciones de WhatsApp?", "[COMPLETAR: Respuesta sobre direcciones incompletas, números de celular mal anotados y errores en platos pedidos]"),
            ("5.3", "¿Ha ocurrido que confirmen un pedido de delivery por WhatsApp y al momento de despacharlo el plato ya no esté disponible?", "[COMPLETAR: Respuesta sobre confirmaciones erróneas de platos agotados y cancelaciones forzosas]"),
            ("5.4", "¿Cómo afecta la saturación de mensajes de WhatsApp a la imagen y reputación del Restaurante “Doña Ross”?", "[COMPLETAR: Respuesta sobre quejas de clientes por falta de respuesta oportuna y pérdida de ventas]"),
            ("5.5", "¿Estarían de acuerdo en centralizar los pedidos en un carrito web donde el cliente elija directamente su pedido?", "[COMPLETAR: Respuesta de total conformidad para descongestionar la línea telefónica y WhatsApp]")
        ]),
        ("Bloque 6: Requerimientos funcionales y expectativas del sistema web", [
            ("6.1", "¿Qué funciones principales consideran indispensables que tenga la página web accesible para los clientes?", "[COMPLETAR: Respuesta sobre ver el menú del día, fotos de platos, precios, estado abierto/cerrado y carrito]"),
            ("6.2", "¿Qué roles o perfiles de usuario identifican en su negocio y qué accesos restringidos debe tener cada uno?", "[COMPLETAR: Respuesta sobre roles: Administrador (acceso total), Cajero (ventas y caja), Cocina (stock y pedidos)]"),
            ("6.3", "¿Qué tipo de reportes administrativos necesitan consultar periódicamente para tomar decisiones comerciales?", "[COMPLETAR: Respuesta sobre reportes de ventas diarias, ingresos mensuales y ranking de platos más vendidos]"),
            ("6.4", "¿Requieren poder modificar los precios, descripciones de platos, promociones y horarios sin depender de un programador?", "[COMPLETAR: Respuesta afirmativa sobre necesidad de un panel administrativo CMS intuitivo y accesible]"),
            ("6.5", "¿Qué nivel de conocimiento tecnológico tiene su personal operativo y cómo esperan que sea la interfaz gráfica?", "[COMPLETAR: Respuesta sobre manejo básico de teléfonos inteligentes, demandando botones grandes y diseño claro]")
        ])
    ]

    entrevista_rows = []
    for tema, items in preguntas_d:
        entrevista_rows.append([tema, "", ""])
        for codigo, preg, resp in items:
            entrevista_rows.append([codigo, preg, resp])

    create_table_custom(doc, ["Código", "Pregunta Formulada", "Espacio para Respuesta del Entrevistado"], 
                        entrevista_rows, col_widths=[Inches(0.8), Inches(2.7), Inches(3.0)],
                        table_title="Tabla 37: Guía estructurada de preguntas de la entrevista a propietarios",
                        table_note="Nota. Elaboración propia (2026).")

    add_h2("D.3. Constancia de validación de la entrevista")
    add_p("Las preguntas y requerimientos descritos en la presente guía fueron consensuados y aprobados por los propietarios del establecimiento como base vinculante para la especificación de casos de uso y modelos de la ingeniería web.")

    # Tabla de firmas
    firmas_d = [
        ["Entrevistado (Propietaria)", "Entrevistado (Propietario / Caja)", "Postulante / Entrevistador"],
        ["\n\n___________________________________\nSra. Gabriela Peralta Limachi\nC.I. [COMPLETAR: C.I.]\nPropietaria - Restaurante Doña Ross\nFirma: [COMPLETAR: Firma]",
         "\n\n___________________________________\nSr. Marco García Mamani\nC.I. [COMPLETAR: C.I.]\nPropietario / Caja - Restaurante Doña Ross\nFirma: [COMPLETAR: Firma]",
         "\n\n___________________________________\nErik Jesus Saavedra Luna\nC.I. [COMPLETAR: C.I.]\nPostulante - Sistemas Informáticos\nFirma: [COMPLETAR: Firma]"]
    ]
    create_table_custom(doc, ["Validación Propietaria", "Validación Propietario", "Responsable del Proyecto"], 
                        [firmas_d[1]], col_widths=[Inches(2.1), Inches(2.2), Inches(2.2)],
                        table_title=None, table_note=None)

    doc.add_page_break()

    # ==========================================
    # ANEXO E
    # ==========================================
    print("Generando Anexo E...")
    add_h1("ANEXO E: FICHA DE OBSERVACIÓN DE CAMPO")

    add_p("De acuerdo con la fundamentación del método inductivo expuesta en los Capítulos I y II del proyecto de grado, la observación directa en campo constituyó la técnica inicial para registrar la realidad operativa del Restaurante “Doña Ross”. Mediante sesiones presenciales en el salón de atención, el área de caja y la cocina, se identificaron los tiempos invertidos, las fallas de coordinación y los cuellos de botella generados por la manipulación de registros en papel y canales informales.")

    add_h2("E.1. Ficha descriptiva de observación")
    add_bullet("Restaurante “Doña Ross”, ubicado en Ciudad Satélite, El Alto - Bolivia.", "Establecimiento observado: ")
    add_bullet("Salón principal de comensales, barra de caja y área de preparación en cocina.", "Áreas inspeccionadas: ")
    add_bullet("Erik Jesus Saavedra Luna (Postulante a Técnico Superior).", "Observador: ")
    add_bullet("[COMPLETAR: Fechas de las jornadas de observación, ej. del 18 de mayo al 29 de mayo de 2026].", "Periodo de ejecución: ")
    add_bullet("11:30 a 15:30 (horario central que concentra el 85 % de la demanda diaria de almuerzos).", "Horario de observación: ")

    add_h2("E.2. Matriz de registro de procesos operativos y demoras detectadas")
    add_p("A continuación, se presenta la matriz estructurada con los procesos observados, las demoras cronometradas, las incidencias operativas identificadas y su impacto en el rendimiento comercial del negocio:")

    obs_headers = ["N°", "Proceso Operativo Observado", "Área / Personal", "Demora / Tiempo Detectado", "Incidencias y Deficiencias Detectadas", "Impacto Operativo"]
    obs_rows = [
        ["1", "Toma de comandas en salón", "Salón / Meseros (Esther e Israel)", "3 a 5 minutos por mesa", 
         "Anotación a mano en talonarios de papel; tachaduras en pedidos modificados; dificultad de lectura por letra apresurada.",
         "Retraso en el envío de la orden a la cocina y errores en el despacho de guarniciones."],
        ["2", "Atención de pedidos vía WhatsApp", "Caja / Propietario (Marco García)", "4 a 8 minutos por comensal", 
         "Respuesta manual individual; necesidad de enviar imágenes fotográficas del menú; tipeo manual de precios y cálculo de total.",
         "Saturación de la línea telefónica, comensales en espera y pérdida de ventas por falta de respuesta oportuna."],
        ["3", "Verificación de disponibilidad de platos", "Salón / Cocina (Omar y Mari)", "1 a 3 minutos por consulta", 
         "El mesero o cajero debe desplazarse físicamente o gritar a la cocina para consultar si aún queda silpancho o fricasé.",
         "Interrupción constante a los cocineros, confusión acústica en hora pico y oferta involuntaria de platos agotados."],
        ["4", "Despacho y entrega de delivery", "Cocina / Repartidor", "5 a 10 minutos de retraso acumulado", 
         "Direcciones anotadas en papel sin referencias claras; comanda extraviada momentáneamente entre los platos preparados.",
         "Platos entregados con demora, quejas de clientes por comida tibia y pérdida de fidelización de comensales."],
        ["5", "Cobro presencial en salón", "Caja (Marco García)", "2 a 4 minutos por cliente", 
         "Suma manual de la comanda con calculadora; búsqueda física de cambio en monedas y billetes; verificación visual de captura QR en pantalla ajena.",
         "Filas de espera en el mostrador para pagar al salir y riesgo de entregar cambio incorrecto."],
        ["6", "Cuadre y arqueo de caja al cierre", "Caja y Administración (Gabriela y Marco)", "35 a 60 minutos al finalizar turno", 
         "Conteo manual de dinero físico; suma de hojas de comandas sueltas; deducción de papelitos de egresos menores (compras urgentes).",
         "Descuadres frecuentes entre efectivo y notas; imposibilidad de determinar si la diferencia obedece a robo, merma o error de cambio."],
        ["7", "Control de descarte y mermas", "Cocina (Omar y Mari)", "Sin registro sistemático (0 min)", 
         "Platos quemados, derramados o sobrantes al final del día se descartan sin anotar cantidad ni justificación formal.",
         "Falta de control de mermas de porciones, distorsión en la rentabilidad real y descontrol de costos de producción."],
        ["8", "[COMPLETAR: Proceso adicional observado]", "[COMPLETAR: Personal]", "[COMPLETAR: Tiempo]", "[COMPLETAR: Incidencias observadas]", "[COMPLETAR: Impacto operativo]"],
        ["9", "[COMPLETAR: Proceso adicional observado]", "[COMPLETAR: Personal]", "[COMPLETAR: Tiempo]", "[COMPLETAR: Incidencias observadas]", "[COMPLETAR: Impacto operativo]"],
        ["10", "[COMPLETAR: Proceso adicional observado]", "[COMPLETAR: Personal]", "[COMPLETAR: Tiempo]", "[COMPLETAR: Incidencias observadas]", "[COMPLETAR: Impacto operativo]"]
    ]

    create_table_custom(doc, obs_headers, obs_rows, 
                        col_widths=[Inches(0.4), Inches(1.3), Inches(1.0), Inches(1.0), Inches(1.4), Inches(1.4)],
                        table_title="Tabla 38: Ficha de observación de campo de procesos operativos",
                        table_note="Nota. Elaboración propia (2026).")

    add_h2("E.3. Conclusiones técnicas de la observación directa")
    add_p("La observación en campo confirmó de forma empírica que el 100 % de los retrasos críticos en el Restaurante “Doña Ross” se originaban en la manipulación fragmentada de registros en papel y en la ausencia de un canal centralizado que vincule de forma simultánea los pedidos de delivery, el control de porciones en cocina y el flujo de caja diario.")

    doc.add_page_break()

    # ==========================================
    # ANEXO F
    # ==========================================
    print("Generando Anexo F...")
    add_h1("ANEXO F: MATRIZ DE ANÁLISIS DOCUMENTAL")

    add_p("El análisis documental consistió en la revisión minuciosa y extracción de campos de datos a partir de los documentos físicos (talonarios de comandas, cuadernos empastados de balance de caja, pizarras de cocina) y digitales (conversaciones de chat en WhatsApp) empleados tradicionalmente por el Restaurante “Doña Ross”. El propósito técnico de esta matriz fue identificar los atributos requeridos para el diseño del modelo entidad-relación y el diccionario de datos del sistema web.")

    add_h2("F.1. Matriz técnica de extracción y correspondencia de datos")

    doc_headers = ["Documento Fuente", "Soporte Actual", "Campos de Datos Identificados", "Frecuencia de Registro", "Deficiencias Detectadas", "Tabla de Destino (Sistema Web)"]
    doc_rows = [
        ["Comandero manual de salón", "Talonario de papel autocopiativo", 
         "Número de mesa, cantidad de platos, nombre del platillo, especificaciones (sin cebolla, ají extra), total a cobrar.", 
         "Por cada orden presencial atendida", 
         "Pérdida física de hojas, escritura ilegible, manchas de grasa, omisión de precios unitarios.", 
         "ventas\nventa_items"],
        ["Cuaderno de balance de caja", "Cuaderno empastado de 100 hojas a cuadros", 
         "Fecha de jornada, monto inicial en cajón, listado de ingresos en efectivo, total de cobros por QR, egresos del día (pan, verduras, refrescos), dinero contado final, diferencia (faltante/sobrante).", 
         "Diario (al cierre del turno)", 
         "Cálculos aritméticos manuales erróneos, tachaduras, falta de firmas de auditoría, desorden cronológico.", 
         "cajas\ncaja_movimientos"],
        ["Conversaciones de WhatsApp", "Chat en dispositivo móvil personal", 
         "Nombre del cliente, número de teléfono/WhatsApp, dirección de entrega, referencias del domicilio, platos solicitados, cantidad, método de pago convenido (efectivo/QR), foto del comprobante QR.", 
         "Por cada pedido de delivery", 
         "Datos de ubicación ambiguos, comprobantes QR falsos o no contrastados en el momento, demoras de transcripción.", 
         "users\npedidos\npedido_items"],
        ["Pizarra de preparación en cocina", "Pizarra acrílica en pared de cocina", 
         "Nombres de platos del día, cantidad estimada de porciones cocinadas en ollas grandes, marcas manuales de platos terminados.", 
         "Diario (al inicio del servicio de cocina)", 
         "Borrones involuntarios, falta de actualización al agregar nuevas ollas (reposiciones), falta de registro de platos quemados.", 
         "stock_jornadas"],
        ["Menú físico plastificado", "Cartulina plastificada en mesas", 
         "Nombre del plato típico, precio en Bolivianos (Bs.), descripción resumida de ingredientes principales.", 
         "Estático (actualización cada varios meses)", 
         "Precios desactualizados tras variaciones de costo en el mercado de El Alto, imposibilidad de mostrar ofertas temporales.", 
         "productos\ncategorias\npromociones"],
        ["Letreros de horario y QR", "Hojas bond impresas pegadas en mostrador", 
         "Horarios de atención de lunes a domingo, código QR simple de cuenta bancaria para transferencias.", 
         "Estático", 
         "Deterioro físico del papel, falta de visibilidad para comensales externos o virtuales.", 
         "horarios\ncontenidos"],
        ["[COMPLETAR: Documento complementario]", "[COMPLETAR: Soporte]", "[COMPLETAR: Campos identificados]", "[COMPLETAR: Frecuencia]", "[COMPLETAR: Deficiencias]", "[COMPLETAR: Tabla destino]"],
        ["[COMPLETAR: Documento complementario]", "[COMPLETAR: Soporte]", "[COMPLETAR: Campos identificados]", "[COMPLETAR: Frecuencia]", "[COMPLETAR: Deficiencias]", "[COMPLETAR: Tabla destino]"]
    ]

    create_table_custom(doc, doc_headers, doc_rows,
                        col_widths=[Inches(1.1), Inches(1.0), Inches(1.5), Inches(0.9), Inches(1.2), Inches(0.8)],
                        table_title="Tabla 39: Matriz de análisis documental de registros manuales y canales informales",
                        table_note="Nota. Elaboración propia (2026).")

    add_h2("F.2. Resultados del análisis documental")
    add_p("A través del análisis documental se demostró que el restaurante manejaba información transaccional crítica, pero de manera dispersa y sin integridad referencial. La estructura de campos extraída sirvió de base directa para la normalización en Tercera Forma Normal (3FN) y la creación de las migraciones de Laravel correspondientes.")

    doc.add_page_break()

    # ==========================================
    # ANEXO G
    # ==========================================
    print("Generando Anexo G...")
    add_h1("ANEXO G: CUESTIONARIO DE USABILIDAD (ESCALA LIKERT 1 A 5) PARA LOS 5 USUARIOS")

    add_p("En concordancia con el marco de calidad de software establecido en el Capítulo IV (norma ISO/IEC 25010:2023, característica de Capacidad de Interacción / Usabilidad), se diseñó y aplicó un cuestionario estructurado bajo la escala Likert de 1 a 5 a la muestra intencional de 5 usuarios clave del sistema web, representativa de todos los perfiles de usuario del restaurante.")

    add_h2("G.1. Participantes y escala de evaluación")
    add_bullet("Sra. Gabriela Peralta Limachi (Propietaria / Gestión Administrativa).", "Usuario 1 (Administrador 1): ")
    add_bullet("Sr. Marco García Mamani (Propietario / Responsable de Caja).", "Usuario 2 (Administrador 2 / Cajero): ")
    add_bullet("Personal operativo del área de caja y salón (Servicio).", "Usuario 3 (Cajero): ")
    add_bullet("Omar (Personal responsable del área de cocina).", "Usuario 4 (Cocina): ")
    add_bullet("Comensal habitual seleccionado en la zona de Ciudad Satélite.", "Usuario 5 (Cliente de prueba): ")

    add_p("Criterio de calificación en escala Likert (1 a 5):", space_before=4, bold=True)
    add_bullet("Totalmente en desacuerdo (Nivel de satisfacción muy bajo / Muy difícil de usar)", "1: ")
    add_bullet("En desacuerdo (Dificultades notorias en la operación / Poco intuitivo)", "2: ")
    add_bullet("Neutral / Indiferente (Cumple de forma básica pero sin destacar)", "3: ")
    add_bullet("De acuerdo (Operación sencilla, comprensible y ágil)", "4: ")
    add_bullet("Totalmente de acuerdo (Excelente usabilidad, muy claro, eficiente y rápido)", "5: ")

    add_h2("G.2. Batería de ítems evaluados por módulo")

    likert_items_headers = ["N°", "Módulo / Dimensión Evaluada", "Enunciado del Ítem / Criterio de Usabilidad", "Subcaracterística ISO 25010"]
    likert_items_rows = [
        ["1", "Catálogo digital y público", "El catálogo de platos presenta fotografías nítidas, descripciones claras y precios visibles de forma ordenada.", "Estética de interfaz"],
        ["2", "Catálogo digital y público", "Es sencillo buscar platos por su nombre y filtrar los platillos según su categoría gastronómica.", "Capacidad de aprendizaje"],
        ["3", "Catálogo digital y público", "El sistema indica con claridad el estado del restaurante (Abierto ahora / Cerrado ahora) y el horario.", "Autodescriptividad"],
        ["4", "Catálogo digital y público", "Se identifica de manera visual e inmediata cuando un plato tradicional se encuentra 'Agotado'.", "Protección contra errores"],
        ["5", "Carrito y pedidos en línea", "El proceso para agregar, modificar porciones y eliminar platillos en el carrito virtual es intuitivo.", "Operabilidad"],
        ["6", "Carrito y pedidos en línea", "El formulario de entrega solicita de forma ordenada los datos indispensables (dirección, celular y notas).", "Reconocimiento adecuación"],
        ["7", "Carrito y pedidos en línea", "La confirmación del pedido genera de inmediato un comprobante digital claro con el total a pagar.", "Completitud funcional"],
        ["8", "Carrito y pedidos en línea", "El cliente puede consultar fácilmente el estado en que se encuentra la preparación de su pedido.", "Asistencia al usuario"],
        ["9", "Control de stock y cocina", "La pantalla de cocina permite cargar con facilidad las porciones iniciales de platos al comenzar el día.", "Operabilidad"],
        ["10", "Control de stock y cocina", "Registrar reposiciones o porciones adicionales durante la jornada es una tarea rápida y sencilla.", "Facilidad de uso"],
        ["11", "Control de stock y cocina", "El sistema exige y facilita el registro obligatorio del motivo al asentar mermas de platos descartados.", "Protección contra errores"],
        ["12", "Control de stock y cocina", "La cola de pedidos entrantes en cocina permite cambiar estados secuencialmente sin complicaciones.", "Eficiencia de interacción"],
        ["13", "Caja y ventas de salón", "El formulario de apertura de caja solicita con claridad el monto inicial en efectivo para iniciar el turno.", "Operabilidad"],
        ["14", "Caja y ventas de salón", "El registro manual de ventas de salón permite seleccionar platos y comensales de manera muy ágil.", "Comportamiento temporal"],
        ["15", "Caja y ventas de salón", "El cálculo del monto total y del cambio en efectivo es automático, exacto y no permite cobros sin caja.", "Protección contra errores"],
        ["16", "Caja y ventas de salón", "El proceso de arqueo y cierre compara transparentemente el dinero contado contra el esperado del sistema.", "Autodescriptividad"],
        ["17", "Administración y CMS", "El panel administrativo (Dashboard) resume con claridad las ventas del día, pedidos y alertas críticas.", "Reconocimiento adecuación"],
        ["18", "Administración y CMS", "Es fácil crear, modificar precios, subir fotos y habilitar o deshabilitar platos en el catálogo.", "Operabilidad"],
        ["19", "Administración y CMS", "La actualización de horarios semanales y datos de contacto se realiza de forma directa sin tocar código.", "Flexibilidad de uso"],
        ["20", "Administración y reportes", "Los reportes gráficos de ventas por día y mensuales permiten analizar la recaudación con total nitidez.", "Comprensibilidad"],
        ["21", "Estética y responsividad", "La interfaz visual utiliza colores cálidos agradables y representativos de la identidad del restaurante.", "Estética de interfaz"],
        ["22", "Estética y responsividad", "La plataforma se adapta adecuadamente a la pantalla del teléfono celular sin desbordes horizontales.", "Flexibilidad / Adaptabilidad"],
        ["23", "Facilidad de aprendizaje", "El personal del restaurante aprendió a utilizar sus módulos respectivos en un tiempo muy breve.", "Capacidad de aprendizaje"],
        ["24", "Satisfacción general", "El sistema web representa una mejora sustancial frente a los cuadernos y mensajes de WhatsApp antiguos.", "Compromiso del usuario"]
    ]

    create_table_custom(doc, likert_items_headers, likert_items_rows,
                        col_widths=[Inches(0.4), Inches(1.6), Inches(3.1), Inches(1.4)],
                        table_title="Tabla 40: Cuestionario de evaluación de usabilidad según norma ISO/IEC 25010",
                        table_note="Nota. Elaboración propia (2026).")

    add_h2("G.3. Matriz de consolidación de resultados de usabilidad")
    add_p("A continuación, se dispone la matriz para asentar las calificaciones individuales (1 a 5) otorgadas por cada uno de los 5 usuarios evaluadores, junto con las columnas para el cálculo de la sumatoria, promedio de cada ítem y porcentaje equivalente:")

    res_headers = ["Ítem", "Criterio / Módulo", "U1\n(Adm 1)", "U2\n(Adm/Caj)", "U3\n(Cajero)", "U4\n(Cocina)", "U5\n(Cliente)", "Suma\n(Σ)", "Promedio\n(x̄)", "Porcentaje\n(%)"]
    
    res_rows = []
    for i in range(1, 25):
        modulo = likert_items_rows[i-1][1]
        res_rows.append([
            str(i),
            f"Ítem {i} ({modulo})",
            "[COMPLETAR: 1-5]",
            "[COMPLETAR: 1-5]",
            "[COMPLETAR: 1-5]",
            "[COMPLETAR: 1-5]",
            "[COMPLETAR: 1-5]",
            "[COMPLETAR: Suma]",
            "[COMPLETAR: x̄]",
            "[COMPLETAR: %]"
        ])

    # Fila final de promedio global
    res_rows.append([
        "—",
        "PROMEDIO Y PORCENTAJE GLOBAL DEL SISTEMA",
        "—", "—", "—", "—", "—",
        "[COMPLETAR: Suma Total]",
        "[COMPLETAR: Promedio Global (ref: 4.6/5)]",
        "[COMPLETAR: Porcentaje Global (ref: 92 %)]"
    ])

    create_table_custom(doc, res_headers, res_rows,
                        col_widths=[Inches(0.4), Inches(1.8), Inches(0.55), Inches(0.55), Inches(0.55), Inches(0.55), Inches(0.55), Inches(0.65), Inches(0.7), Inches(0.7)],
                        table_title="Tabla 41: Matriz de consolidación de resultados de usabilidad (Escala Likert)",
                        table_note="Nota. Elaboración propia (2026). Fórmula del porcentaje: % = (Promedio ÷ 5) × 100.")

    add_p("Nota de consistencia documental: En el Capítulo IV del proyecto de grado (página 113) se reportó una puntuación promedio general de 4.6 sobre 5 puntos, equivalente al 92 % de usabilidad percibida y satisfacción operativa. El estudiante postulante debe asentar en la matriz anterior los valores exactos recopilados en las hojas de respuesta físicas de cada usuario.")

    doc.add_page_break()

    # ==========================================
    # ANEXO H
    # ==========================================
    print("Generando Anexo H...")
    add_h1("ANEXO H: REGISTRO DE MEDICIONES TÉCNICAS (RENDIMIENTO Y VOLUMEN DE CÓDIGO)")

    add_p("El presente anexo documenta las pruebas y mediciones cuantitativas realizadas sobre el sistema web en dos aspectos esenciales de la ingeniería de software: el rendimiento temporal en el servidor de producción cloud Railway (validación del requisito no funcional RNF-04) y el dimensionamiento métrico del volumen de código fuente desarrollado a medida mediante la herramienta estándar CLOC (Count Lines of Code).")

    add_h2("H.1. Registro de mediciones de rendimiento sobre el servidor en la nube Railway")
    add_bullet("https://restaurante-dona-ross-production.up.railway.app/", "Entorno de producción evaluado: ")
    add_bullet("El catálogo, pedido de delivery y venta presencial deben responder en un tiempo menor o igual a 3.0 segundos (≤ 3 s); la generación de reportes consolidados debe responder en un tiempo menor o igual a 5.0 segundos (≤ 5 s).", "Criterio de aceptación (RNF-04): ")
    add_bullet("Navegador Google Chrome en modo incógnito, herramientas para desarrolladores (DevTools, pestaña Red / Network, con opción 'Deshabilitar caché' activada) y auditoría Google Lighthouse.", "Herramientas e instrumental técnico: ")

    add_p("Protocolo de medición: Se realizaron tres (3) ejecuciones independientes para cada transacción clave en horario de alta carga de red, registrando el tiempo de carga completa (Finish / Load) y calculando el tiempo promedio:")

    perf_headers = ["Transacción / Módulo Evaluado", "Endpoint / Ruta HTTP", "Meta RNF-04", "Medición 1 (s)", "Medición 2 (s)", "Medición 3 (s)", "Promedio (s)", "Evaluación"]
    perf_rows = [
        ["Carga de catálogo digital de platos", "GET /catalogo", "≤ 3.0 s", "[COMPLETAR: s]", "[COMPLETAR: s]", "[COMPLETAR: s]", "[COMPLETAR: Promedio s]", "[COMPLETAR: Cumple/No cumple]"],
        ["Confirmación de pedido delivery (Checkout)", "POST /cliente/checkout", "≤ 3.0 s", "[COMPLETAR: s]", "[COMPLETAR: s]", "[COMPLETAR: s]", "[COMPLETAR: Promedio s]", "[COMPLETAR: Cumple/No cumple]"],
        ["Registro de venta presencial en salón", "POST /admin/ventas o /cajero/ventas", "≤ 3.0 s", "[COMPLETAR: s]", "[COMPLETAR: s]", "[COMPLETAR: s]", "[COMPLETAR: Promedio s]", "[COMPLETAR: Cumple/No cumple]"],
        ["Generación de reporte administrativo de ventas", "GET /admin/reportes", "≤ 5.0 s", "[COMPLETAR: s]", "[COMPLETAR: s]", "[COMPLETAR: s]", "[COMPLETAR: Promedio s]", "[COMPLETAR: Cumple/No cumple]"]
    ]

    create_table_custom(doc, perf_headers, perf_rows,
                        col_widths=[Inches(1.6), Inches(1.3), Inches(0.7), Inches(0.65), Inches(0.65), Inches(0.65), Inches(0.75), Inches(0.8)],
                        table_title="Tabla 42: Registro de mediciones de rendimiento en servidor Railway (RNF-04)",
                        table_note="Nota. Elaboración propia (2026).")

    add_h3("Espacio para capturas de pantalla de DevTools / Lighthouse:")
    add_screenshot_box("[COMPLETAR: Pegar aquí la captura de pantalla de Network en Chrome DevTools para la carga del Catálogo]")
    add_screenshot_box("[COMPLETAR: Pegar aquí la captura de pantalla de Network en Chrome DevTools para el Checkout de Delivery]")
    add_screenshot_box("[COMPLETAR: Pegar aquí la captura de pantalla de Network en Chrome DevTools para la Generación de Reportes]")

    add_h2("H.2. Medición del volumen de código fuente desarrollado a medida con CLOC")
    add_p("Para dar soporte cuantitativo a la estimación de esfuerzo del modelo COCOMO II Post-Architecture (Capítulo IV), el tamaño del software se midió con la herramienta de consola CLOC (Count Lines of Code). Con el fin de aislar con absoluta rigurosidad el esfuerzo real del postulante y excluir código de terceros o librerías del framework Laravel, se ejecutó el siguiente comando normalizado:")

    p_cmd = doc.add_paragraph()
    p_cmd.paragraph_format.space_before = Pt(4)
    p_cmd.paragraph_format.space_after = Pt(6)
    p_cmd.paragraph_format.left_indent = Inches(0.4)
    run_cmd = p_cmd.add_run("cloc app routes resources database config --exclude-dir=vendor,node_modules")
    run_cmd.font.name = "Consolas"
    run_cmd.font.size = Pt(10)
    run_cmd.bold = True
    run_cmd.font.color.rgb = RGBColor(180, 40, 20)

    add_p("A continuación, se presenta la tabla estructurada para asentar la salida discriminada por lenguajes y carpetas:")

    cloc_headers = ["Lenguaje de Programación", "Cantidad de Archivos", "Líneas en Blanco (Blank)", "Líneas de Comentarios", "Líneas de Código (SLOC)"]
    cloc_rows = [
        ["PHP (Controladores, Modelos, Requests, Servicios)", "[COMPLETAR: Cant. archivos]", "[COMPLETAR: Líneas]", "[COMPLETAR: Líneas]", "[COMPLETAR: SLOC PHP]"],
        ["Blade / HTML (Plantillas y vistas de interfaz)", "[COMPLETAR: Cant. archivos]", "[COMPLETAR: Líneas]", "[COMPLETAR: Líneas]", "[COMPLETAR: SLOC Blade]"],
        ["JavaScript (Lógica de cliente, carrito, gráficos)", "[COMPLETAR: Cant. archivos]", "[COMPLETAR: Líneas]", "[COMPLETAR: Líneas]", "[COMPLETAR: SLOC JS]"],
        ["CSS (Hojas de estilo personalizadas Doña Ross)", "[COMPLETAR: Cant. archivos]", "[COMPLETAR: Líneas]", "[COMPLETAR: Líneas]", "[COMPLETAR: SLOC CSS]"],
        ["SQL / JSON (Migraciones y configuraciones)", "[COMPLETAR: Cant. archivos]", "[COMPLETAR: Líneas]", "[COMPLETAR: Líneas]", "[COMPLETAR: SLOC SQL]"],
        ["TOTAL GENERAL DESARROLLADO", "[COMPLETAR: Total archivos]", "[COMPLETAR: Total blanco]", "[COMPLETAR: Total coment.]", "[COMPLETAR: Total SLOC (ref: ~4.8 KSLOC)]"]
    ]

    create_table_custom(doc, cloc_headers, cloc_rows,
                        col_widths=[Inches(2.5), Inches(1.0), Inches(1.0), Inches(1.0), Inches(1.5)],
                        table_title="Tabla 43: Registro de métricas de tamaño de código fuente con herramienta CLOC",
                        table_note="Nota. Elaboración propia (2026).")

    add_h3("Espacio para captura o texto de la consola CLOC:")
    add_screenshot_box("[COMPLETAR: Pegar aquí la captura de pantalla de la terminal con la ejecución del comando CLOC o transcribir su salida de texto]")

    doc.add_page_break()

    # ==========================================
    # ANEXO I
    # ==========================================
    print("Generando Anexo I...")
    add_h1("ANEXO I: MANUAL DE USUARIO POR ROL")

    add_p("El presente manual de usuario tiene como objetivo proporcionar una guía gráfica y procedimental paso a paso para la operación eficiente del sistema web del Restaurante “Doña Ross”. El contenido se encuentra organizado estrictamente por roles de usuario, en correspondencia con los casos de uso CU-01 a CU-13 y los cuatro incrementos metodológicos desarrollados en el Capítulo III.")

    add_h2("I.1. Estructura de roles y módulos operativos del sistema")

    roles_map_headers = ["Rol de Usuario", "Casos de Uso Asociados", "Módulos y Privilegios Asignados", "Entorno de Acceso"]
    roles_map_rows = [
        ["Cliente (Comensal externo)", "CU-01, CU-02, CU-03", 
         "Consulta de catálogo, platos del día, promociones, carrito de compras, confirmación de delivery con nota de venta digital y consulta de pedidos.", 
         "Interfaz pública web y /cliente/dashboard"],
        ["Cajero (Personal de caja)", "CU-04, CU-09, CU-10, CU-11", 
         "Apertura de caja diaria, registro de ventas presenciales en salón, cobro de pedidos delivery entregados, movimientos de efectivo y arqueo con cierre.", 
         "Acceso corporativo /login-admin y panel /cajero/caja"],
        ["Cocina (Personal culinario)", "CU-04, CU-07, CU-08", 
         "Carga inicial de porciones diarias, reposición de platos, registro de mermas con motivo, monitoreo de alertas de stock y cola de preparación de pedidos.", 
         "Acceso corporativo /login-admin y panel /cocina/pedidos"],
        ["Administrador (Propietarios)", "CU-04 al CU-08, CU-12, CU-13", 
         "Acceso global a catálogo, platos, categorías, promociones, inventario de stock, usuarios, supervisión de cajas, CMS institucional y reportes.", 
         "Acceso corporativo /login-admin y panel /admin/dashboard"]
    ]

    create_table_custom(doc, roles_map_headers, roles_map_rows,
                        col_widths=[Inches(1.2), Inches(1.1), Inches(2.7), Inches(1.5)],
                        table_title="Tabla 44: Estructura de módulos y casos de uso por rol de usuario",
                        table_note="Nota. Elaboración propia (2026).")

    # ------------------ ROL CLIENTE ------------------
    add_h2("I.2. Manual operativo del Rol Cliente")
    add_p("El rol Cliente está destinado a los comensales que acceden desde sus teléfonos móviles, tabletas o computadoras personales para consultar la oferta gastronómica y realizar pedidos a domicilio.")

    add_h3("Paso 1: Acceso a la página de inicio y verificación del estado del restaurante (CU-01)")
    add_p("1. Abra el navegador web e ingrese a la dirección oficial: https://restaurante-dona-ross-production.up.railway.app/.\n2. En la parte superior, observe el banner institucional y la etiqueta dinámica de estado: 'Abierto ahora' (color verde) o 'Cerrado ahora' (color plomo), calculada automáticamente contra el horario del restaurante.\n3. Explore la sección de 'Platos del Día' y 'Promociones Vigentes' destacadas.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de la página de inicio con indicador de horario y platos del día]")

    add_h3("Paso 2: Exploración del catálogo interactivo y filtrado de platillos (CU-01)")
    add_p("1. Haga clic en la pestaña 'Catálogo' en el menú de navegación.\n2. Utilice los botones de categorías (Entradas, Platos Fuertes, Bebidas, Postres, Combos) para filtrar los productos tradicionales.\n3. En el buscador textual, ingrese el nombre del platillo (ej. 'silpancho', 'fricasé'). Observe la etiqueta 'Agotado' en aquellos platos que no cuenten con porciones disponibles en cocina.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del catálogo público con filtros por categoría y buscador]")

    add_h3("Paso 3: Registro de cuenta e inicio de sesión del cliente (CU-04)")
    add_p("1. Si es un usuario nuevo, haga clic en 'Registrarse', complete su nombre, correo electrónico y contraseña.\n2. Si ya dispone de cuenta, ingrese a 'Acceso Clientes', introduzca su correo y contraseña y presione 'Iniciar Sesión'.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del formulario de registro e inicio de sesión de clientes]")

    add_h3("Paso 4: Adición de platos al carrito de compras virtual (CU-02)")
    add_p("1. En la tarjeta del plato deseado, presione el botón 'Agregar al Carrito'.\n2. Ingrese a la vista de 'Mi Carrito' (ícono superior) para revisar la lista de platillos seleccionados.\n3. Ajuste las cantidades deseadas mediante los botones '+ / -' o elimine platos presionando el ícono de papelera. El subtotal y total se recalculan en tiempo real.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de la vista del carrito de compras virtual con cantidades]")

    add_h3("Paso 5: Registro de datos de entrega y confirmación del pedido (CU-03)")
    add_p("1. En la pantalla del carrito, revise el resumen del pedido y descienda a la sección 'Datos de Entrega'.\n2. Ingrese la dirección exacta del domicilio en Ciudad Satélite o zonas autorizadas.\n3. Proporcione su número de teléfono o celular con WhatsApp para la coordinación del repartidor.\n4. Ingrese referencias adicionales (ej. 'Puerta verde, a media cuadra del mercado Satélite').\n5. Seleccione la modalidad de pago (Efectivo o Transferencia QR) y presione el botón 'Confirmar y Pedir Ahora'.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del formulario de datos de entrega y confirmación de delivery]")

    add_h3("Paso 6: Visualización de la Nota de Venta Digital y comprobante (CU-03)")
    add_p("1. Al procesarse la solicitud, el sistema descuenta automáticamente el stock y muestra en pantalla la Nota de Venta Digital con el número de pedido (#Pedido), lista de ítems, precio unitario, subtotal y total a pagar en Bs.\n2. Si seleccionó pago mediante QR, visualice en pantalla el código QR estático oficial de Doña Ross para realizar la transferencia correspondiente.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de la nota de venta digital / ticket de pedido generado]")

    add_h3("Paso 7: Seguimiento del estado del pedido en el panel de cliente (CU-08)")
    add_p("1. Acceda a la sección 'Mis Pedidos' desde el menú de usuario.\n2. Verifique en tiempo real el avance de su orden a través de los estados: 'Pendiente', 'Confirmado', 'En preparación', 'Despachado' y 'Entregado'.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del historial y seguimiento de estados de pedidos del cliente]")

    # ------------------ ROL CAJERO ------------------
    add_h2("I.3. Manual operativo del Rol Cajero")
    add_p("El rol Cajero es responsable de la administración de la caja física, la apertura con fondo inicial, el registro ágil de ventas en salón, el cobro de pedidos delivery y el arqueo al cierre de turno.")

    add_h3("Paso 1: Inicio de sesión corporativo de personal (CU-04)")
    add_p("1. Ingrese a la dirección `/login-admin` o presione el enlace 'Acceso Personal / Admin' al pie del sitio web.\n2. Introduzca su correo corporativo de cajero y su contraseña cifrada.\n3. Presione 'Ingresar'. El middleware verificará su rol y lo redirigirá al panel de control de caja.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del formulario de acceso corporativo para personal y cajero]")

    add_h3("Paso 2: Apertura de caja con fondo inicial en Bolivianos (CU-09)")
    add_p("1. Si la jornada no ha sido abierta, el sistema bloqueará las ventas y mostrará un aviso indicando que debe abrir su caja.\n2. Presione el botón 'Abrir Caja'.\n3. Ingrese el monto de dinero físico con el que inicia el turno (ej. '200.00' Bs.) y una observación opcional (ej. 'Fondo de cambio mañana').\n4. Presione 'Confirmar Apertura'. La caja quedará en estado 'Abierta'.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del módulo de apertura de caja con monto inicial]")

    add_h3("Paso 3: Registro de ventas presenciales en salón (CU-10)")
    add_p("1. En el menú lateral o superior, presione 'Registrar Venta'.\n2. Seleccione el comensal asignado (cliente general de salón o registrado).\n3. Agregue los platillos solicitados seleccionando de la lista de productos disponibles (el sistema solo lista platos con porciones mayores a cero).\n4. Seleccione el método de pago: 'Efectivo' o 'QR'.\n5. Si el pago es en efectivo, ingrese el 'Monto Recibido'. El sistema calculará automáticamente en el servidor el cambio exacto a devolver al comensal.\n6. Presione 'Registrar y Cobrar Venta'. El sistema registrará la venta, descontará el stock en cocina y sumará el ingreso a la caja abierta.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del formulario de registro de venta presencial en salón]")

    add_h3("Paso 4: Emisión y visualización del ticket de venta de salón (CU-10)")
    add_p("1. Al confirmarse el cobro, el sistema redirecciona al comprobante oficial de venta (#Ticket) con desglose de ítems, hora de atención y cajero responsable.\n2. La vista permite imprimir el comprobante si se cuenta con impresora térmica o mantenerlo en el registro histórico.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del ticket / comprobante de venta de salón]")

    add_h3("Paso 5: Confirmación de cobro de pedidos de delivery entregados (CU-08, CU-11)")
    add_p("1. Acceda a la pestaña 'Cobros de Delivery' o 'Pedidos Pendientes de Cobro'.\n2. Ubique los pedidos en estado 'Entregado' que hayan sido rendidos por el repartidor.\n3. Seleccione 'Confirmar Cobro', elija si se canceló en 'Efectivo' o mediante 'QR' y guarde la transacción. El pedido quedará asociado a la caja activa con su fecha y hora exacta (`pagado_at`).")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de la interfaz de confirmación de cobro de pedidos de delivery]")

    add_h3("Paso 6: Registro de movimientos menores de efectivo en caja (CU-09)")
    add_p("1. En caso de requerir un retiro de dinero para compra imprevista de insumos (ej. pan o hielo) o recibir un ingreso extraordinario, ingrese a 'Movimientos de Caja'.\n2. Seleccione el tipo ('Entrada' o 'Salida'), especifique el monto en Bolivianos y escriba el concepto obligatorio.\n3. Estos movimientos afectarán directamente el arqueo final para evitar falsos descuadres.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de registro de entradas y salidas de efectivo en caja]")

    add_h3("Paso 7: Arqueo físico y cierre de caja al finalizar la jornada (CU-11)")
    add_p("1. Al terminar el turno laboral, seleccione 'Cerrar Caja y Arqueo'.\n2. El sistema calculará internamente el monto total esperado mediante la fórmula: Monto inicial + Ventas de salón + Cobros de delivery + Entradas − Salidas.\n3. Cuente físicamente el dinero en billetes y monedas del cajón e ingrese el total en el campo 'Monto Declarado'.\n4. Ingrese una observación de cierre y presione 'Cerrar Caja Definitivamente'. El sistema congelará la caja, calculará la diferencia (marcando faltante con saldo negativo o sobrante con positivo) y registrará el arqueo auditado.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del formulario de arqueo y confirmación de cierre de caja]")

    # ------------------ ROL COCINA ------------------
    add_h2("I.4. Manual operativo del Rol Cocina")
    add_p("El rol Cocina permite al personal culinario gestionar en tiempo real la disponibilidad de porciones preparadas y controlar el avance en la preparación de las comandas de delivery.")

    add_h3("Paso 1: Inicio de sesión del personal de cocina (CU-04)")
    add_p("1. Ingrese con el usuario asignado a cocina en `/login-admin`.\n2. El sistema abrirá directamente el panel de cocina, restringiendo el acceso a reportes financieros y caja.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de acceso de inicio de sesión del personal de cocina]")

    add_h3("Paso 2: Carga inicial de porciones al inicio de la jornada (CU-07)")
    add_p("1. Al comenzar el turno (10:30 a 11:30 a.m.), ingrese al módulo 'Control de Stock'.\n2. Para cada plato que se preparó ese día (ej. Silpancho, Pique Macho, Fricasé, Costillar), presione 'Cargar Stock'.\n3. Ingrese la cantidad de porciones iniciales preparadas en cocina (ej. 35 porciones de silpancho).\n4. Guarde el registro. Inmediatamente el catálogo web actualizará las porciones disponibles para clientes y cajeros.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de la pantalla de carga inicial de porciones del día]")

    add_h3("Paso 3: Reposición de porciones durante el servicio (CU-07)")
    add_p("1. Si en cocina se elabora una segunda tanda de un plato, presione 'Reponer'.\n2. Ingrese la cantidad de porciones que se suman al inventario del día y confirme. El sistema sumará este valor en la columna de entradas de la jornada.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de la función de reposición de porciones en cocina]")

    add_h3("Paso 4: Registro obligatorio de mermas de porciones (CU-07)")
    add_p("1. Si una o varias porciones sufren deterioro, sobrecocción, quemado o caída accidental, presione 'Registrar Merma'.\n2. Ingrese el número de porciones descartadas y redacte obligatoriamente el motivo específico (la validación del sistema impide guardar mermas sin justificación).\n3. El sistema descontará las unidades de las disponibles y dejará constancia para la administración.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de registro de merma de porciones con motivo justificado]")

    add_h3("Paso 5: Monitoreo de alertas de bajo stock y platos agotados (CU-07)")
    add_p("1. En la tabla de stock, observe las alertas visuales: los platos con 5 o menos porciones se resaltan con badge amarillo de advertencia.\n2. Cuando el stock llega a 0, el sistema muestra la etiqueta roja 'Agotado' y bloquea automáticamente cualquier intento de compra tanto en el carrito web como en las ventas de salón.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de alertas de bajo stock y etiqueta de plato agotado]")

    add_h3("Paso 6: Gestión de la cola de pedidos entrantes de delivery (CU-08)")
    add_p("1. Diríjase a 'Pedidos de Cocina' para visualizar en pantalla las órdenes entrantes ordenadas cronológicamente.\n2. Cada tarjeta detalla los platillos y especificaciones de notas del cliente.\n3. Presione el botón 'En preparación' al comenzar a servir la comanda.\n4. Al empaquetar el pedido y entregarlo al repartidor, presione 'Despachado'. El estado se actualizará de forma simultánea para el cliente y el administrador.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del tablero de comandas y pedidos entrantes en cocina]")

    # ------------------ ROL ADMINISTRADOR ------------------
    add_h2("I.5. Manual operativo del Rol Administrador")
    add_p("El rol Administrador otorga control integral a los propietarios (Gabriela Peralta y Marco García) para configurar el menú, administrar usuarios, supervisar cajas, editar horarios y generar reportes financieros.")

    add_h3("Paso 1: Acceso al panel administrativo y Dashboard principal (CU-04)")
    add_p("1. Inicie sesión con la cuenta de Administrador.\n2. Visualice en el Dashboard los indicadores del día: ventas de hoy en Bs., pedidos de delivery pendientes de despacho, total de platos registrados y alertas de existencias.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del Dashboard principal con indicadores y resumen administrativo]")

    add_h3("Paso 2: Gestión del catálogo de platos y productos (CU-05)")
    add_p("1. En el menú lateral, seleccione 'Productos'.\n2. Para registrar un nuevo plato, presione 'Nuevo Producto', complete el nombre, categoría, precio en Bs., porciones iniciales y cargue la fotografía del platillo (máx. 2 MB).\n3. Para modificar un plato existente, presione 'Editar', actualice el precio o descripción y guarde.\n4. Utilice el interruptor para marcar un plato como 'Destacado' en la portada o para desactivarlo temporalmente del menú.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del módulo de gestión, alta y edición de platos]")

    add_h3("Paso 3: Administración de categorías del menú (CU-05)")
    add_p("1. Acceda a 'Categorías'.\n2. Cree nuevas clasificaciones (ej. Platos Fuertes, Sopas Tradicionales, Jugos Naturales, Postres) o cambie su estado a 'activo' o 'inactivo'.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de la gestión de categorías de platos]")

    add_h3("Paso 4: Creación y gestión de promociones con vigencia (CU-06)")
    add_p("1. Ingrese a 'Promociones'.\n2. Presione 'Nueva Promoción', defina el título (ej. '2x1 los Jueves'), porcentaje de descuento, fecha de inicio y fecha de expiración.\n3. El sistema mostrará la promoción en la portada pública únicamente durante el rango de fechas programado.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de la administración de promociones y vigencia]")

    add_h3("Paso 5: Administración de cuentas de usuario y personal (CU-04)")
    add_p("1. Diríjase a 'Personal' o 'Usuarios'.\n2. Cree nuevos accesos asignando el rol correspondiente ('administrador', 'cajero', 'cocina').\n3. En caso de cese de funciones de un empleado, presione 'Desactivar Cuenta' para denegar el acceso al sistema sin borrar su historial de operaciones.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de la gestión y asignación de roles de usuarios]")

    add_h3("Paso 6: Supervisión de cajas y auditoría de arqueos diarios (CU-09, CU-11)")
    add_p("1. Ingrese al módulo 'Cajas y Arqueos'.\n2. Consulte el historial de cajas abiertas y cerradas por fecha y cajero.\n3. Presione 'Ver' en una caja cerrada para auditar el detalle de ventas en salón, cobros de delivery y verificar si existieron diferencias (faltantes o sobrantes) junto a las observaciones registradas.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla del historial de cajas y auditoría de arqueos]")

    add_h3("Paso 7: CMS Institucional: Edición de horarios y contacto (CU-12)")
    add_p("1. Acceda a 'Contenido del Sitio' / 'Horarios'.\n2. Para cada día de la semana (Lunes a Domingo), configure la hora de apertura y cierre, o desmarque el día en caso de descanso.\n3. Actualice el número de teléfono/WhatsApp y reemplace la imagen del código QR oficial de cobro sin necesidad de modificar el código fuente del servidor.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de la interfaz de configuración de horarios y datos de contacto CMS]")

    add_h3("Paso 8: Generación y análisis de reportes estadísticos de ventas (CU-13)")
    add_p("1. Ingrese a la sección 'Reportes'.\n2. Seleccione el período de análisis (últimos 14 días, mensual o personalizado).\n3. Analice el gráfico interactivo de barras con la recaudación discriminada entre ventas de salón y cobros de delivery.\n4. Revise la tabla del ranking de 'Platos más Vendidos', la cual totaliza unidades despachadas e importe generado excluyendo pedidos cancelados.")
    add_screenshot_box("[COMPLETAR: Captura de pantalla de los reportes gráficos de ventas y ranking de platos]")

    doc.add_page_break()

    # ==========================================
    # ANEXO J
    # ==========================================
    print("Generando Anexo J...")
    add_h1("ANEXO J: MANUAL TÉCNICO Y DE INSTALACIÓN")

    add_p("El presente documento constituye la guía técnica oficial para el despliegue, configuración y mantenimiento del sistema web del Restaurante “Doña Ross”. Proporciona los lineamientos de instalación tanto en el entorno local de desarrollo basado en XAMPP bajo Windows, como en la plataforma en la nube PaaS Railway para el entorno de producción en vivo.")

    add_h2("J.1. Requisitos de infraestructura y software")
    add_bullet("Computadora con procesador x64 (mínimo 2 núcleos a 2.0 GHz, recomendado 4 núcleos o superior), memoria RAM mínima de 8 GB (utilizados 12 GB en la máquina de desarrollo), y almacenamiento en unidad de estado sólido (SSD) con al menos 10 GB de espacio libre.", "Hardware del servidor local: ")
    add_bullet("Windows 10 / 11 de 64 bits o distribución Linux (Ubuntu 22.04 LTS / Debian 12).", "Sistema operativo: ")
    add_bullet("Versión 8.2.12 o superior, con extensiones activas: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo y curl.", "PHP: ")
    add_bullet("Composer versión 2.9.5 o superior (gestor oficial de dependencias de PHP).", "Gestor de dependencias: ")
    add_bullet("MariaDB 10.4.32 (incluida en el paquete XAMPP v3.3.0) o MySQL Server 8.0.", "Base de datos relacional: ")
    add_bullet("Laravel Framework versión 12.69.3 / 12.70.0 bajo arquitectura Modelo-Vista-Controlador.", "Framework Backend: ")
    add_bullet("Bootstrap 5.3.3 (vía CDN), Bootstrap Icons 1.11.3 y Chart.js para visualizaciones estadísticas.", "Frontend: ")
    add_bullet("Git versión 2.52.0 para control de versiones distribuido.", "Control de versiones: ")

    add_h2("J.2. Procedimiento de instalación en entorno local (XAMPP / Windows)")
    add_p("Para desplegar el sistema en una estación de trabajo local, ejecute la siguiente secuencia técnica de pasos:")

    add_p("Paso 1: Clonación del código fuente.", bold=True)
    add_p("Abra una terminal de comandos (PowerShell o Git Bash) y clone el repositorio oficial dentro del directorio raíz de documentos web de XAMPP:")
    add_p("cd C:\\xampp\\htdocs\ngit clone https://github.com/ErikSaavedra/Restaurante-Dona-Ross.git Proyecto\ncd Proyecto", italic=True)

    add_p("Paso 2: Instalación de paquetes de dependencias con Composer.", bold=True)
    add_p("Ejecute la descarga e instalación de las librerías requeridas por el framework Laravel:")
    add_p("composer install", italic=True)

    add_p("Paso 3: Creación y configuración del entorno (.env).", bold=True)
    add_p("Genere el archivo de configuración local a partir de la plantilla de ejemplo:")
    add_p("copy .env.example .env", italic=True)

    add_p("Paso 4: Generación de la clave criptográfica de la aplicación.", bold=True)
    add_p("Ejecute el comando de Artisan para establecer el valor de `APP_KEY` necesario para el cifrado de sesiones y contraseñas:")
    add_p("php artisan key:generate", italic=True)

    add_p("Paso 5: Configuración de la base de datos en MariaDB / MySQL.", bold=True)
    add_p("Abra el panel de control de XAMPP e inicie los módulos Apache y MySQL. Ingrese a phpMyAdmin (http://localhost/phpmyadmin) y cree una nueva base de datos denominada `bd_dona_ross` con cotejamiento `utf8mb4_unicode_ci`.\nLuego, edite el archivo `.env` verificando los siguientes parámetros locales:")
    add_p("DB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=bd_dona_ross\nDB_USERNAME=root\nDB_PASSWORD=", italic=True)

    add_p("Paso 6: Enlace simbólico de almacenamiento de archivos multimedia.", bold=True)
    add_p("Cree el enlace simbólico para que las imágenes de los platos almacenadas en `storage/app/public` sean accesibles públicamente desde el navegador:")
    add_p("php artisan storage:link", italic=True)

    add_p("Paso 7: Ejecución de migraciones y sembrado de datos iniciales.", bold=True)
    add_p("Ejecute la estructura completa de base de datos y cargue los usuarios por defecto, categorías y platos iniciales:")
    add_p("php artisan migrate --seed", italic=True)

    add_p("Paso 8: Puesta en marcha del servidor local.", bold=True)
    add_p("Inicie el servicio mediante el comando integrado de Laravel o mediante VirtualHost de Apache:")
    add_p("php artisan serve", italic=True)
    add_p("El sistema quedará operativo y accesible localmente en la dirección: http://127.0.0.1:8000 o http://localhost/Proyecto/public.")

    add_h2("J.3. Parámetros y variables de entorno del sistema (.env)")
    add_p("La siguiente tabla detalla las variables de entorno que gobiernan la plataforma, describiendo su finalidad y valores de referencia sin comprometer credenciales ni contraseñas confidenciales:")

    env_headers = ["Variable de Entorno", "Valor de Referencia / Entorno", "Descripción y Finalidad Técnica"]
    env_rows = [
        ["APP_NAME", "Restaurante Doña Ross", "Nombre institucional del sistema mostrado en títulos y correos."],
        ["APP_ENV", "local / production", "Entorno de ejecución (desarrollo local o producción en la nube)."],
        ["APP_KEY", "base64:... (generada con Artisan)", "Clave criptográfica de 32 caracteres para hash y cifrado."],
        ["APP_DEBUG", "true (local) / false (producción)", "Control de visualización de errores y trazas de depuración."],
        ["APP_URL", "http://localhost:8000 / https://restaurante-dona-ross-production.up.railway.app/", "URL canónica base de la aplicación web."],
        ["LOG_CHANNEL", "stack", "Canal de almacenamiento de logs de eventos y errores."],
        ["LOG_LEVEL", "debug (local) / error (producción)", "Nivel mínimo de severidad para registrar eventos en logs."],
        ["DB_CONNECTION", "mysql", "Driver del motor relacional de base de datos."],
        ["DB_HOST", "127.0.0.1 / ${{MySQL.MYSQLHOST}}", "Dirección IP o host del servidor de base de datos."],
        ["DB_PORT", "3306 / ${{MySQL.MYSQLPORT}}", "Puerto de conexión del gestor relacional."],
        ["DB_DATABASE", "bd_dona_ross / railway", "Nombre de la base de datos de la plataforma."],
        ["DB_USERNAME", "root / ${{MySQL.MYSQLUSER}}", "Usuario con permisos de lectura, escritura y esquema."],
        ["DB_PASSWORD", "(vacío en local) / ${{MySQL.MYSQLPASSWORD}}", "Contraseña protegida de acceso al motor de datos."],
        ["SESSION_DRIVER", "file / database", "Mecanismo de persistencia de las sesiones de usuario y carrito."],
        ["FILESYSTEM_DISK", "public", "Almacenamiento de archivos multimedia y fotos de catálogo."]
    ]

    create_table_custom(doc, env_headers, env_rows,
                        col_widths=[Inches(1.8), Inches(2.2), Inches(2.7)],
                        table_title="Tabla 45: Variables y parámetros de configuración del entorno (.env)",
                        table_note="Nota. Elaboración propia (2026).")

    add_h2("J.4. Guía de despliegue en la plataforma Cloud Railway (Producción)")
    add_p("El despliegue en producción se ejecutó en la plataforma PaaS Railway conectada mediante integración continua al repositorio de GitHub:")

    add_bullet("Se vinculó el repositorio oficial de GitHub (rama 'main') al proyecto de Railway para habilitar despliegues automáticos (CI/CD) ante cada push confirmado.", "Vinculación con GitHub: ")
    add_bullet("Se aprovisionó un servicio nativo de base de datos MySQL dentro del mismo contenedor del proyecto en Railway, asegurando comunicación interna por red privada de alto rendimiento.", "Aprovisionamiento de MySQL: ")
    add_bullet("Se definieron las variables de entorno en el panel de Railway configurando estrictamente `APP_ENV=production` y `APP_DEBUG=false` para garantizar la protección de la información ante fallos no controlados (requisito RNF-03).", "Variables de producción: ")
    add_bullet("Railway compila automáticamente el contenedor mediante Nixpacks, ejecutando la instalación optimizada de dependencias: `composer install --optimize-autoloader --no-dev`, la generación de caché de rutas y configuraciones (`php artisan config:cache`, `php artisan route:cache`), y la ejecución segura de migraciones: `php artisan migrate --force`.", "Automatización del Build y Deploy: ")
    add_bullet("La plataforma genera automáticamente el certificado SSL (HTTPS) garantizando una navegación cifrada de extremo a extremo.", "Seguridad de transporte HTTPS: ")

    add_h2("J.5. Políticas de copias de seguridad (Backups) y restauración")
    add_p("Para proteger los datos financieros, ventas y pedidos frente a eventuales contingencias, se establecen los siguientes procedimientos operativos:")

    add_p("Respaldo periódico en entorno local (XAMPP):", bold=True)
    add_p("Ejecute en la consola de comandos la utilidad `mysqldump` para generar un volcado íntegro de datos y estructura:")
    add_p("C:\\xampp\\mysql\\bin\\mysqldump.exe -u root bd_dona_ross > C:\\Respaldos\\bd_dona_ross_backup_%date:~-4,4%%date:~-7,2%%date:~-10,2%.sql", italic=True)

    add_p("Restauración de la base de datos:", bold=True)
    add_p("Para restaurar la información a partir de un volcado SQL previo:")
    add_p("C:\\xampp\\mysql\\bin\\mysql.exe -u root bd_dona_ross < C:\\Respaldos\\bd_dona_ross_backup_YYYYMMDD.sql", italic=True)

    add_p("Mantenimiento de archivos de Log:", bold=True)
    add_p("El archivo `storage/logs/laravel.log` debe inspeccionarse periódicamente para identificar intentos de inyección, accesos denegados (código 403) o incidencias de conexión.")

    doc.add_page_break()

    # ==========================================
    # ANEXO K
    # ==========================================
    print("Generando Anexo K...")
    add_h1("ANEXO K: ACTA DE ENTREGA Y CAPACITACIÓN DEL SISTEMA")

    add_p("En las instalaciones del Restaurante “Doña Ross”, ubicado en la zona de Ciudad Satélite de la ciudad de El Alto, departamento de La Paz, Estado Plurinacional de Bolivia, a horas [COMPLETAR: Hora de inicio] del día [COMPLETAR: Fecha de entrega y capacitación, ej. 12 de noviembre de 2026], se reunieron los representantes legales del establecimiento gastronómico y el postulante de la Carrera de Sistemas Informáticos del Instituto Tecnológico Bolivia Mar, a objeto de suscribir formalmente la presente ACTA DE ENTREGA Y CAPACITACIÓN DEL SISTEMA WEB DE VENTAS, CONTROL DE STOCK Y PEDIDOS EN LÍNEA.")

    add_h2("K.1. Datos de las partes intervinientes")
    add_bullet("Restaurante “Doña Ross”, microempresa familiar inscrita bajo el Régimen Tributario Simplificado con NIT 6060991015, con domicilio legal en Ciudad Satélite, El Alto, representada por sus propietarios: la Sra. Gabriela Peralta Limachi y el Sr. Marco García Mamani.", "Establecimiento beneficiario: ")
    add_bullet("Erik Jesus Saavedra Luna, con C.I. [COMPLETAR: C.I.], estudiante egresado de la Carrera de Sistemas Informáticos del Instituto Tecnológico Bolivia Mar (ITBM).", "Postulante responsable: ")
    add_bullet("M.Sc. Richard Jhosepes Tacachira Pérez (Tutor Metodológico) y Lic. Hilda Callisaya Apaza (Tutora Técnica).", "Tutores académicos: ")

    add_h2("K.2. Contenido y temario de la capacitación impartida por roles")
    add_p("El postulante impartió la capacitación teórico-práctica al personal del establecimiento según el siguiente cronograma de módulos temáticos:")

    cap_headers = ["Módulo de Capacitación", "Rol del Personal", "Temas y Competencias Desarrolladas", "Duración", "Evaluación Práctica"]
    cap_rows = [
        ["Módulo 1: Catálogo y Pedidos", "Cliente y Meseros (Esther e Israel)", 
         "Navegación del catálogo web, verificación del estado abierto/cerrado, adición de platos al carrito, registro de dirección en Satélite y generación de nota de venta.", 
         "1.5 horas", "Registro simulado de pedidos de delivery exitoso."],
        ["Módulo 2: Control de Stock y Cocina", "Cocina (Omar y Mari)", 
         "Carga inicial de porciones al comenzar el turno, registro de reposición de platos, registro obligatorio de mermas con motivo y manejo de cola de pedidos entrantes.", 
         "2.0 horas", "Carga real de porciones y descarte de merma con motivo."],
        ["Módulo 3: Caja y Ventas de Salón", "Cajero (Marco García)", 
         "Apertura de caja con fondo inicial en Bs., registro ágil de ventas en salón, cálculo exacto de cambio, confirmación de cobro de delivery y arqueo al cierre.", 
         "2.5 horas", "Apertura, 3 ventas de prueba y arqueo coincidente."],
        ["Módulo 4: Administración y CMS", "Administrador (Gabriela Peralta)", 
         "Dashboard de ventas, alta y edición de precios y fotos de platos, promociones con vigencia, horarios semanales, QR de cobro y generación de reportes.", 
         "2.0 horas", "Edición de precios y emisión de reporte mensual."]
    ]

    create_table_custom(doc, cap_headers, cap_rows,
                        col_widths=[Inches(1.3), Inches(1.2), Inches(2.5), Inches(0.8), Inches(1.2)],
                        table_title="Tabla 46: Cronograma de contenidos temáticos de capacitación por rol",
                        table_note="Nota. Elaboración propia (2026).")

    add_h2("K.3. Registro de asistencia y conformidad del personal capacitado")
    add_p("Los asistentes que formaron parte de la jornada de capacitación y validaron el correcto entendimiento y manejo de sus respectivos módulos firman al pie del presente cuadro:")

    asist_headers = ["Nombre y Apellidos", "Cargo / Rol en Doña Ross", "Cédula de Identidad (C.I.)", "Firma de Conformidad"]
    asist_rows = [
        ["Sra. Gabriela Peralta Limachi", "Propietaria / Administradora", "[COMPLETAR: C.I.]", "[COMPLETAR: Firma]"],
        ["Sr. Marco García Mamani", "Propietario / Cajero", "[COMPLETAR: C.I.]", "[COMPLETAR: Firma]"],
        ["Omar [COMPLETAR: Apellidos]", "Encargado de Cocina", "[COMPLETAR: C.I.]", "[COMPLETAR: Firma]"],
        ["Mari [COMPLETAR: Apellidos]", "Ayudante de Cocina", "[COMPLETAR: C.I.]", "[COMPLETAR: Firma]"],
        ["Esther [COMPLETAR: Apellidos]", "Atención y Mesera", "[COMPLETAR: C.I.]", "[COMPLETAR: Firma]"],
        ["Israel [COMPLETAR: Apellidos]", "Atención y Mesero", "[COMPLETAR: C.I.]", "[COMPLETAR: Firma]"],
        ["Erik Jesus Saavedra Luna", "Postulante / Capacitador", "[COMPLETAR: C.I.]", "[COMPLETAR: Firma]"]
    ]

    create_table_custom(doc, asist_headers, asist_rows,
                        col_widths=[Inches(2.2), Inches(2.0), Inches(1.3), Inches(1.5)],
                        table_title="Tabla 47: Registro de asistencia y conformidad de la capacitación",
                        table_note="Nota. Elaboración propia (2026).")

    add_h2("K.4. Declaración formal de entrega de la solución tecnológica")
    add_p("Por medio de la presente, el postulante Erik Jesus Saavedra Luna hace entrega formal a los propietarios del Restaurante “Doña Ross” de los siguientes elementos:")
    add_bullet("Sistema web completamente instalado y desplegado en producción en el servidor en la nube Railway (URL: https://restaurante-dona-ross-production.up.railway.app/).", "1. Software en línea: ")
    add_bullet("Repositorio completo con el código fuente en Laravel 12 y la base de datos relacional MySQL / MariaDB.", "2. Código fuente: ")
    add_bullet("Credenciales de acceso maestro para el Administrador, Cajero y Cocina, con recomendación de cambio periódico.", "3. Cuentas de acceso: ")
    add_bullet("Manuales impresos y digitales de usuario por rol (Anexo I) y manual técnico de instalación y mantenimiento (Anexo J).", "4. Documentación: ")

    add_p("En señal de plena conformidad y agradecimiento recíproco, se firma la presente acta en dos ejemplares de un mismo tenor y efecto legal en la ciudad de El Alto, a los [COMPLETAR: Día] días del mes de [COMPLETAR: Mes] del año 2026.")

    doc.add_page_break()

    # ==========================================
    # ANEXO L
    # ==========================================
    print("Generando Anexo L...")
    add_h1("ANEXO L: CARTA DE ACEPTACIÓN DEL SISTEMA")

    add_p("El Alto, [COMPLETAR: Día] de [COMPLETAR: Mes] de 2026", space_before=10, space_after=14, align=WD_ALIGN_PARAGRAPH.RIGHT, bold=True)

    add_p("Señores:\nINSTITUTO TECNOLÓGICO BOLIVIA MAR\nCARRERA DE SISTEMAS INFORMÁTICOS\nTUTORES DE PROYECTO DE GRADO:\nM.Sc. Richard Jhosepes Tacachira Pérez (Tutor Metodológico)\nLic. Hilda Callisaya Apaza (Tutora Técnica)\nPresente.-", space_before=0, space_after=14, bold=True)

    add_p("REF.: CARTA FORMAL DE ACEPTACIÓN, CONFORMIDAD Y AGRADECIMIENTO POR LA IMPLEMENTACIÓN DEL SISTEMA INFORMÁTICO", space_before=0, space_after=16, align=WD_ALIGN_PARAGRAPH.CENTER, bold=True)

    add_p("De nuestra mayor consideración:")

    add_p("Mediante la presente, nosotros, GABRIELA PERALTA LIMACHI con C.I. [COMPLETAR: C.I.] y MARCO GARCÍA MAMANI con C.I. [COMPLETAR: C.I.], en calidad de legítimos propietarios y administradores del RESTAURANTE “DOÑA ROSS”, establecimiento gastronómico ubicado en la zona de Ciudad Satélite de la ciudad de El Alto y titular del NIT 6060991015, nos dirigimos a su prestigiosa institución académica para manifestar lo siguiente:")

    add_p("PRIMERO (CONFORMIDAD DEL SISTEMA).- Certificamos que el estudiante postulante ERIK JESUS SAAVEDRA LUNA, con C.I. [COMPLETAR: C.I.], ha desarrollado e implementado en nuestro restaurante el Proyecto de Grado titulado: “SISTEMA WEB DE VENTAS, CONTROL DE STOCK Y PEDIDOS EN LÍNEA. CASO: RESTAURANTE 'DOÑA ROSS'”, habiendo cumplido a cabalidad con todos los objetivos y requerimientos acordados.")

    add_p("SEGUNDO (BENEFICIO OPERATIVO Y COMERCIAL).- Dejamos constancia de que el sistema web se encuentra funcionando de forma óptima en nuestro establecimiento tanto en computadoras de escritorio como en teléfonos celulares. La solución ha resuelto problemas históricos de nuestra microempresa, permitiéndonos:")
    add_bullet("Reemplazar los cuadernos y notas de papel por un registro de ventas de salón exacto e integrado con el control de caja diaria.", "a) ")
    add_bullet("Eliminar los descuadres de dinero en efectivo mediante el procedimiento de arqueo diario al cierre de cada jornada.", "b) ")
    add_bullet("Controlar en tiempo real las porciones preparadas en cocina (descontando el stock automáticamente con cada pedido o venta y bloqueando la oferta de platillos agotados).", "c) ")
    add_bullet("Optimizar la atención de pedidos de delivery a través de un catálogo digital interactivo y carrito en línea, reduciendo la saturación de mensajes que sufríamos en la línea de WhatsApp.", "d) ")
    add_bullet("Consultar reportes estadísticos claros sobre los platos más vendidos e ingresos diarios y mensuales para una mejor toma de decisiones.", "e) ")

    add_p("TERCERO (CAPACITACIÓN Y DOCUMENTACIÓN).- Confirmamos que el postulante brindó las sesiones de capacitación a todo nuestro personal (cocineros, cajero y meseros), logrando que nos adaptemos rápidamente al manejo de la plataforma, y nos hizo entrega de los manuales de usuario y las credenciales maestras de acceso.")

    add_p("CUARTO (ACEPTACIÓN DEFINITIVA).- Por todo lo expuesto, expresamos nuestra ACEPTACIÓN DEFINITIVA Y PLENA SATISFACCIÓN con el software desarrollado, agradeciendo al Instituto Tecnológico Bolivia Mar por vincular los conocimientos técnicos de sus estudiantes con las necesidades reales de los negocios familiares de la ciudad de El Alto.")

    add_p("Con este motivo, saludamos a ustedes con las consideraciones más distinguidas.")

    add_p("Atentamente,", space_before=16, space_after=36, align=WD_ALIGN_PARAGRAPH.CENTER, bold=True)

    # Tabla de firmas de los propietarios
    firmas_l = [
        ["_________________________________________\nSra. Gabriela Peralta Limachi\nC.I. [COMPLETAR: C.I.]\nPROPIETARIA\nRestaurante “Doña Ross”\nFirma: [COMPLETAR: Firma]",
         "_________________________________________\nSr. Marco García Mamani\nC.I. [COMPLETAR: C.I.]\nPROPIETARIO / CAJA\nRestaurante “Doña Ross”\nFirma: [COMPLETAR: Firma]"],
        ["[COMPLETAR: Espacio para sello del Restaurante Doña Ross]",
         "[COMPLETAR: Espacio para sello del Restaurante Doña Ross]"]
    ]

    create_table_custom(doc, ["Propietaria", "Propietario / Caja"], 
                        [firmas_l[0]], col_widths=[Inches(3.2), Inches(3.2)],
                        table_title=None, table_note=None)

    add_screenshot_box("[COMPLETAR: Espacio para estampado de Sello oficial del Restaurante Doña Ross (NIT 6060991015)]")

    # Guardado del documento
    output_path = os.path.join(os.getcwd(), "Anexos_del_Proyecto_de_Grado_Dona_Ross.docx")
    doc.save(output_path)
    print(f"Documento generado exitosamente en: {output_path}")
    return output_path

if __name__ == "__main__":
    build_anexos_document()
