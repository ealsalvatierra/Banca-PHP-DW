// Inspección de fuente: no importa ni ejecuta PHP/SQL.
const fs=require('node:fs');const path=require('node:path');
const root=process.argv[2]||path.resolve(__dirname,'../ProyectoBanca');
const files=Object.fromEntries(fs.readdirSync(root).filter(n=>/\.(php|sql|css)$/.test(n)).map(n=>[n,fs.readFileSync(path.join(root,n),'utf8')]));
const tests=[];const check=(name,ok)=>tests.push({name,status:ok?'PASS':'FAIL'});
const php=Object.keys(files).filter(n=>n.endsWith('.php'));const security=files['seguridad.php'];const sql=files['db.sql'];
const migration=fs.readFileSync(path.join(root,'migrations/001_seguridad_y_transacciones.sql'),'utf8');
check('Includes existentes',php.every(n=>[...files[n].matchAll(/(?:require|include)(?:_once)?\s*['"]([^'"]+)['"]/g)].every(m=>Object.hasOwn(files,m[1]))));
check('Sin modo prueba',php.every(n=>!files[n].includes('MODO PRUEBA')));
for(const role of ['admin','cajero','usuario']) {
 const guard=files['verificar_'+role+'.php'];const login=files['login_'+role+'.php'];
 check('Guardia '+role+' exige rol',guard.includes("exigir_rol('"+role+"',")&&!guard.includes("$_SESSION['rol'] ="));
 check('Login '+role+' verifica credencial',login.includes("autenticar($mysqli, '"+role+"',"));
 check('Login '+role+' renueva sesión',login.includes('session_regenerate_id(true)'));
 check('Login '+role+' marca autenticación',login.includes("$_SESSION['autenticada'] = true"));
}
check('Guardia común rechaza sesión no autenticada',security.includes("($_SESSION['autenticada'] ?? false) !== true")&&security.includes("$_SESSION['rol'] !== $rol"));
check('Sesión del banco separada',security.includes("session_name('BANCA_SESSION')"));
check('Cookies HttpOnly y SameSite Strict',security.includes("'httponly' => true")&&security.includes("'samesite' => 'Strict'"));
check('Sesión expira y respuestas no se cachean',security.includes('> 1800')&&security.includes('Cache-Control: no-store'));
check('Cajero bloqueado pierde acceso activo',files['verificar_cajero.php'].includes('SELECT activo FROM cajeros WHERE usuario = ?')&&files['verificar_cajero.php'].includes("$cajero['activo'] !== 1"));
check('Cuenta de usuario se obtiene del propietario real',files['verificar_usuario.php'].includes('SELECT no_cuenta FROM usuarios WHERE correo = ?')&&!files['verificar_usuario.php'].includes('1001'));
const handlers=php.filter(n=>files[n].includes("REQUEST_METHOD'] === 'POST'"));
check('Handlers POST validan token',handlers.every(n=>files[n].includes('validar_csrf();')));
check('Logout solo POST con token',files['logout.php'].includes("REQUEST_METHOD'] !== 'POST'")&&files['logout.php'].includes('validar_csrf();'));
check('Todo formulario POST incorpora token',php.every(n=>[...files[n].matchAll(/<form\b[^>]*method="POST"[^>]*>([\s\S]*?)<\/form>/gi)].every(m=>m[1].includes('csrf_campo()'))));
check('Token se consume y tiene caducidad',security.includes("unset($_SESSION['csrf'][$token])")&&security.includes("time() - $_SESSION['csrf'][$token] >= 1800"));
check('Claves nuevas almacenadas con hash',['registro.php','admin_cajeros.php'].every(n=>files[n].includes('password_hash($clave, PASSWORD_DEFAULT)')));
check('Verifica hash y migra credenciales antiguas',security.includes('password_verify($clave')&&security.includes('password_needs_rehash')&&security.includes('UPDATE $tabla SET clave = ?'));
check('Columnas y parámetros admiten hash completo',(sql.match(/clave VARCHAR\(255\)/g)||[]).length===5&&(migration.match(/MODIFY clave VARCHAR\(255\)/g)||[]).length===3);
check('Fixture inicial ya tiene bcrypt',/\('admin', '\$2y\$10\$/.test(sql));
check('Inputs no escalares son rechazados',security.includes("return is_string($valor) ? trim($valor) : ''"));
check('Dinero no usa notación científica ni más de dos decimales',security.includes('[0-9]{1,10}(?:\\.[0-9]{1,2})?'));
check('Altas y operaciones usan validador de dinero',['cajero_crear_cuenta.php','cajero_deposito.php','cajero_retiro.php','usuario_transferencia.php','usuario_terceros.php'].every(n=>files[n].includes('monto_valido(')));
check('Se drenan resultados de procedimientos',security.includes('more_results()')&&security.includes('next_result()')&&['registro.php','usuario_transferencia.php','cajero_deposito.php','cajero_retiro.php'].every(n=>files[n].includes('limpiar_resultados($mysqli)')));
check('Sin exposición directa del error SQL',php.filter(n=>n!=='seguridad.php').every(n=>!n.endsWith('conexion.php')?!files[n].includes('. $stmt->error'):!files[n].includes('. $mysqli->connect_error')));
check('Todas las tablas nuevas son InnoDB',(sql.match(/ENGINE=InnoDB/g)||[]).length===7);
check('Migración convierte las siete tablas',(migration.match(/ALTER TABLE \w+ ENGINE=InnoDB/g)||[]).length===7);
for(const name of ['sp_deposito','sp_retiro','sp_transferir']) {
 const body=sql.match(new RegExp('CREATE PROCEDURE '+name+'\\b[\\s\\S]*?END\\$\\$'))?.[0]||'';
 check(name+' tiene transacción, rollback y bloqueo',body.includes('START TRANSACTION')&&body.includes('COMMIT')&&body.includes('ROLLBACK')&&body.includes('FOR UPDATE'));
 check(name+' rechaza monto no positivo',body.includes('p_monto IS NULL OR p_monto <= 0'));
}
check('Retiro verifica saldo dentro de la transacción',sql.includes('IF v_saldo < p_monto THEN'));
check('Transferencia obtiene tercero por propietario',sql.includes('WHERE id = p_tercero AND correo_usuario = p_correo FOR UPDATE'));
check('Transferencia aplica límite diario bajo bloqueo',sql.includes('IF v_total >= v_limite THEN')&&sql.includes('INTERVAL 1 DAY FOR UPDATE'));
check('Transferencia impide destino propio',sql.includes('IF v_origen = v_destino THEN'));
check('Registro tiene unicidad de cuenta SQL',sql.includes('UNIQUE KEY uq_usuarios_cuenta (no_cuenta)'));
check('Migración no borra tablas ni registros',!/(?:DROP\s+TABLE|TRUNCATE|DELETE\s+FROM)/i.test(migration));
const summary={total:tests.length,pass:tests.filter(t=>t.status==='PASS').length,fail:tests.filter(t=>t.status==='FAIL').length};
console.log(JSON.stringify({scope:'regresión estática, sin ejecución PHP/SQL',summary,tests},null,2));
process.exitCode=summary.fail?1:0;
