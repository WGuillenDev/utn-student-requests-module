"""Fills the official SLR-002 "Solicitud de Levantamiento de Requisito"
editable PDF (resources/documents/slr-002-levantamiento.pdf) with a
student's data, so the constancia the student receives by email is the
real institutional form rather than a re-creation.

Invoked by BrowsershotResolutionDocumentGenerator (PHP) via a JSON spec
file, because PHP has no AcroForm-filling library in this project while
pypdf is available on the host:

    python scripts/fill_slr002.py <spec.json>

spec.json shape:
    {
        "template": "absolute path to the blank SLR-002 PDF",
        "output": "absolute path to write the filled PDF",
        "fields": {"Text1": "...", ...},
        "checkbox": "Button17"   # optional; the justification to tick
    }

Field map (from the template's own AcroForm annotations):
    Text1  Primer apellido       Text2  Segundo apellido
    Text3  Nombre                Text4  Número de identificación
    Text5  Sede                  Text6  Carrera
    Text7/Text12   course row 1 (código / nombre)
    Text8/Text11   course row 2   Text9/Text13  course row 3
    Text10/Text14  course row 4
    Button15..Button19 = justifications a..e (on-state '/No')
"""

import json
import sys

from pypdf import PdfReader, PdfWriter
from pypdf.generic import NameObject


def main() -> int:
    with open(sys.argv[1], encoding="utf-8") as handle:
        spec = json.load(handle)

    reader = PdfReader(spec["template"])
    writer = PdfWriter()
    writer.append(reader)

    values = dict(spec.get("fields", {}))
    if spec.get("checkbox"):
        values[spec["checkbox"]] = "/No"  # the template's checked state

    writer.update_page_form_field_values(
        writer.pages[0], values, auto_regenerate=False
    )

    # Ask viewers to (re)build field appearances so the typed values are
    # visible everywhere, including viewers that ignore auto_regenerate.
    root = writer._root_object
    if "/AcroForm" in root:
        from pypdf.generic import BooleanObject

        root["/AcroForm"][NameObject("/NeedAppearances")] = BooleanObject(True)

    with open(spec["output"], "wb") as out:
        writer.write(out)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
