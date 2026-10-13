#!/usr/bin/env python3
# Convert docs/SRS.md -> docs/SRS.html (self-contained, styled)
import html, re, sys

SRC = "docs/SRS.md"
DST = "docs/SRS.html"

text = open(SRC, encoding="utf-8").read()
lines = text.split("\n")

CSS = """
body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; line-height: 1.5; max-width: 900px; margin: 32px auto; padding: 0 24px; }
h1 { font-size: 26px; border-bottom: 3px solid #f59e0b; padding-bottom: 8px; }
h2 { font-size: 20px; margin-top: 28px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; color: #111827; }
h3 { font-size: 16px; margin-top: 20px; color: #374151; }
table { border-collapse: collapse; width: 100%; margin: 12px 0; font-size: 13px; }
th, td { border: 1px solid #cbd5e1; padding: 6px 9px; text-align: left; vertical-align: top; }
th { background: #f3f4f6; }
code { background: #f3f4f6; padding: 1px 4px; border-radius: 4px; font-family: Consolas, monospace; font-size: 12px; }
pre { background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px; overflow: auto; }
pre code { background: none; padding: 0; }
blockquote { border-left: 4px solid #f59e0b; margin: 8px 0; padding: 4px 12px; color: #4b5563; background: #fffbeb; }
hr { border: none; border-top: 1px solid #d1d5db; margin: 20px 0; }
"""

def inline(s):
    s = html.escape(s)
    s = re.sub(r"`([^`]+)`", r"<code>\1</code>", s)
    s = re.sub(r"\*\*([^*]+)\*\*", r"<strong>\1</strong>", s)
    s = re.sub(r"\*([^*]+)\*", r"<em>\1</em>", s)
    return s

out = []
i = 0
n = len(lines)
in_code = False
code_buf = []
in_list = None  # 'ul' or 'ol'

def close_list():
    global in_list
    if in_list:
        out.append("</%s>" % in_list)
        in_list = None

while i < n:
    line = lines[i]
    stripped = line.strip()

    # code fence
    if stripped.startswith("```"):
        if in_code:
            out.append("<pre><code>%s</code></pre>" % html.escape("\n".join(code_buf)))
            code_buf = []
            in_code = False
        else:
            close_list()
            in_code = True
            code_buf = []
        i += 1
        continue
    if in_code:
        code_buf.append(line)
        i += 1
        continue

    # horizontal rule
    if re.fullmatch(r"-{3,}", stripped):
        close_list()
        out.append("<hr/>")
        i += 1
        continue

    # headings
    m = re.match(r"^(#{1,6})\s+(.*)$", stripped)
    if m:
        close_list()
        lvl = len(m.group(1))
        out.append("<h%d>%s</h%d>" % (lvl, inline(m.group(2)), lvl))
        i += 1
        continue

    # table: current line starts with | and next non-empty line is separator
    if stripped.startswith("|"):
        # gather table block
        block = []
        while i < n and lines[i].strip().startswith("|"):
            block.append(lines[i].strip())
            i += 1
        rows = []
        for b in block:
            cells = [c.strip() for c in b.strip().strip("|").split("|")]
            rows.append(cells)
        # drop separator row (cells like ---)
        rows = [r for r in rows if not all(re.fullmatch(r":?-{2,}:?", c) for c in r)]
        close_list()
        out.append("<table>")
        for ri, r in enumerate(rows):
            tag = "th" if ri == 0 else "td"
            out.append("<tr>" + "".join("<%s>%s</%s>" % (tag, inline(c), tag) for c in r) + "</tr>")
        out.append("</table>")
        continue

    # blockquote
    if stripped.startswith(">"):
        close_list()
        out.append("<blockquote>%s</blockquote>" % inline(stripped.lstrip("> ")))
        i += 1
        continue

    # unordered list
    m = re.match(r"^-\s+(.*)$", stripped)
    if m:
        if in_list != "ul":
            close_list()
            out.append("<ul>")
            in_list = "ul"
        out.append("<li>%s</li>" % inline(m.group(1)))
        i += 1
        continue

    # ordered list
    m = re.match(r"^\d+[.)]\s+(.*)$", stripped)
    if m:
        if in_list != "ol":
            close_list()
            out.append("<ol>")
            in_list = "ol"
        out.append("<li>%s</li>" % inline(m.group(1)))
        i += 1
        continue

    # blank line
    if not stripped:
        close_list()
        i += 1
        continue

    # paragraph
    close_list()
    out.append("<p>%s</p>" % inline(stripped))
    i += 1

close_list()

html_doc = "<!DOCTYPE html><html><head><meta charset='utf-8'><style>%s</style></head><body>%s</body></html>" % (CSS, "\n".join(out))
open(DST, "w", encoding="utf-8").write(html_doc)
print("WROTE", DST, len(html_doc), "bytes")
