import sys

def process(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    idx1 = content.find('<!-- Step 1:')
    idx2 = content.find('<!-- Step 2:')
    idx3 = content.find('<!-- Step 3:')
    idx4 = content.find('<!-- Step 4:')
    idxEnd = content.find('</div> <!-- FIN DEL GRID -->')
    
    if idx1 == -1 or idx2 == -1 or idx3 == -1 or idx4 == -1 or idxEnd == -1:
        print("Markers not found in", filepath)
        return
        
    before = content[:idx1]
    import re
    before = re.sub(
        r'<div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6 lg:gap-8 mb-8 items-start">\s*$',
        '<div class="flex flex-col lg:flex-row gap-4 sm:gap-6 lg:gap-8 mb-8 items-start">\n' +
        '        <!-- COLUMNA IZQUIERDA -->\n' +
        '        <div class="flex-1 flex flex-col gap-4 sm:gap-6 lg:gap-8 min-w-0 lg:w-1/2 w-full">\n        ',
        before
    )
    
    step1 = content[idx1:idx2]
    step2 = content[idx2:idx3]
    step3 = content[idx3:idx4]
    step4 = content[idx4:idxEnd]
    after = content[idxEnd + len('</div> <!-- FIN DEL GRID -->'):]
    
    # Clean step2
    step2 = step2.replace('md:row-span-3 ', '')
    
    new_content = before + step1 + step3 + step4 + \
        '        </div> <!-- FIN COLUMNA IZQUIERDA -->\n\n' + \
        '        <!-- COLUMNA DERECHA -->\n' + \
        '        <div class="flex-1 flex flex-col gap-4 sm:gap-6 lg:gap-8 min-w-0 lg:w-1/2 w-full">\n        ' + \
        step2 + \
        '        </div> <!-- FIN COLUMNA DERECHA -->\n\n' + \
        '    </div> <!-- FIN DEL FLEX ROW -->\n' + after
        
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print("Done", filepath)

process('e:/PROYECTOS/ProyectoNegocio/modules/inventory/views/create.php')
process('e:/PROYECTOS/ProyectoNegocio/modules/inventory/views/edit.php')
