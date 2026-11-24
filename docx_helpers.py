# -*- coding: utf-8 -*-
"""
Generador del documento Word de Anexos del Proyecto de Grado
Restaurante "Doña Ross" - Instituto Tecnológico Bolivia Mar (ITBM)
Postulante: Erik Jesus Saavedra Luna
Año: 2026
"""

import os
import re
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_COLOR_INDEX
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn

def set_cell_margins(cell, top=120, bottom=120, left=150, right=150):
    """Establece márgenes internos (padding) de una celda en dxa (1 pt = 20 dxa)"""
    tcPr = cell._tc.get_or_add_tcPr()
    tcMar = parse_xml(f'''
        <w:tcMar {nsdecls("w")}>
            <w:top w:w="{top}" w:type="dxa"/>
            <w:bottom w:w="{bottom}" w:type="dxa"/>
            <w:left w:w="{left}" w:type="dxa"/>
            <w:right w:w="{right}" w:type="dxa"/>
        </w:tcMar>
    ''')
    tcPr.append(tcMar)

def set_cell_shading(cell, color="F2F2F2"):
    """Establece color de fondo de una celda"""
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{color}" w:val="clear"/>')
    tcPr.append(shd)

def set_table_borders(table, border_color="B0B0B0", inside_color="D3D3D3"):
    """Establece bordes simples y sutiles a la tabla"""
    tblPr = table._tbl.tblPr
    borders = parse_xml(f'''
        <w:tblBorders {nsdecls("w")}>
            <w:top w:val="single" w:sz="6" w:space="0" w:color="{border_color}"/>
            <w:left w:val="single" w:sz="6" w:space="0" w:color="{border_color}"/>
            <w:bottom w:val="single" w:sz="6" w:space="0" w:color="{border_color}"/>
            <w:right w:val="single" w:sz="6" w:space="0" w:color="{border_color}"/>
            <w:insideH w:val="single" w:sz="4" w:space="0" w:color="{inside_color}"/>
            <w:insideV w:val="single" w:sz="4" w:space="0" w:color="{inside_color}"/>
        </w:tblBorders>
    ''')
    tblPr.append(borders)

def add_styled_text(paragraph, text, default_font_size=11, default_bold=False, default_italic=False, default_color=None):
    """
    Agrega texto a un párrafo procesando patrones [COMPLETAR: ...]
    para marcarlos con resaltado amarillo.
    """
    parts = re.split(r'(\[COMPLETAR:[^\]]*\])', text)
    for part in parts:
        if not part:
            continue
        run = paragraph.add_run(part)
        run.font.name = 'Arial'
        run.font.size = Pt(default_font_size)
        if default_color:
            run.font.color.rgb = default_color
        
        if part.startswith('[COMPLETAR:') and part.endswith(']'):
            run.font.highlight_color = WD_COLOR_INDEX.YELLOW
            run.bold = True
        else:
            run.bold = default_bold
            run.italic = default_italic

def create_table_custom(doc, headers, rows_data, col_widths=None, table_title=None, table_note="Nota. Elaboración propia (2026)."):
    """Crea una tabla con formato APA / Institucional estándar"""
    if table_title:
        p_title = doc.add_paragraph()
        p_title.paragraph_format.space_before = Pt(8)
        p_title.paragraph_format.space_after = Pt(3)
        p_title.paragraph_format.keep_with_next = True
        run_t = p_title.add_run(table_title)
        run_t.font.name = 'Arial'
        run_t.font.size = Pt(10)
        run_t.bold = True
        run_t.font.color.rgb = RGBColor(40, 40, 40)

    num_rows = len(rows_data) + 1
    num_cols = len(headers)
    table = doc.add_table(rows=num_rows, cols=num_cols)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    set_table_borders(table)

    # Encabezados
    hdr_row = table.rows[0]
    trPr = hdr_row._tr.get_or_add_trPr()
    trPr.append(parse_xml(f'<w:tblHeader {nsdecls("w")}/>'))
    trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))

    for idx, heading in enumerate(headers):
        cell = hdr_row.cells[idx]
        set_cell_margins(cell, top=140, bottom=140, left=150, right=150)
        set_cell_shading(cell, "EAECEE")
        p = cell.paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(0)
        p.paragraph_format.line_spacing = 1.05
        run = p.add_run(heading)
        run.font.name = 'Arial'
        run.font.size = Pt(9.5)
        run.bold = True
        run.font.color.rgb = RGBColor(20, 20, 20)

    # Filas de datos
    for r_idx, row_values in enumerate(rows_data):
        row = table.rows[r_idx + 1]
        trPr = row._tr.get_or_add_trPr()
        trPr.append(parse_xml(f'<w:cantSplit {nsdecls("w")}/>'))
        
        # Alternar color muy suave de fila para legibilidad
        bg_color = "FAFAFA" if r_idx % 2 == 1 else "FFFFFF"

        for c_idx, cell_value in enumerate(row_values):
            cell = row.cells[c_idx]
            set_cell_margins(cell, top=100, bottom=100, left=130, right=130)
            if bg_color != "FFFFFF":
                set_cell_shading(cell, bg_color)
            
            p = cell.paragraphs[0]
            p.paragraph_format.space_before = Pt(0)
            p.paragraph_format.space_after = Pt(0)
            p.paragraph_format.line_spacing = 1.1
            
            # Si el texto es corto o numérico, alinear al centro
            str_val = str(cell_value).strip()
            if len(str_val) <= 6 and (str_val.isdigit() or str_val in ["1", "2", "3", "4", "5", "—", "Bs.", "N°", "OK", "0", "1FN", "2FN", "3FN"]):
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            else:
                p.alignment = WD_ALIGN_PARAGRAPH.LEFT
                
            add_styled_text(p, str_val, default_font_size=9.5)

    # Anchos de columna si están definidos
    if col_widths:
        for row in table.rows:
            for idx, width in enumerate(col_widths):
                if idx < len(row.cells):
                    row.cells[idx].width = width

    # Nota de tabla
    if table_note:
        p_note = doc.add_paragraph()
        p_note.paragraph_format.space_before = Pt(3)
        p_note.paragraph_format.space_after = Pt(10)
        run_n = p_note.add_run(table_note)
        run_n.font.name = 'Arial'
        run_n.font.size = Pt(8.5)
        run_n.italic = True
        run_n.font.color.rgb = RGBColor(90, 90, 90)

    return table

print("Base helper module defined!")
