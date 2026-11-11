"use strict";

const fs = require("fs");
const path = require("path");
const {
  glob,
  rename,
  download,
  FileFsRef,
  FileBlob,
  getNodeVersion,
} = require("@vercel/build-utils");
const libphp = require("@libphp/almalinux-9-v85");
const {
  getLauncherFiles,
  runComposerInstall,
  runComposerScripts,
} = require("vercel-php/dist/utils");

const BRIDGE_HANDLER = [
  "async function __bridgeBody(stream) {",
  "    var chunks = [];",
  "    for await (var chunk of stream) chunks.push(chunk);",
  "    return Buffer.concat(chunks);",
  "}",
  "",
  "async function bridgeHandler(reqOrEvent, res) {",
  '    var modern = reqOrEvent && typeof reqOrEvent.url === "string" &&',
  '        res && typeof res.writeHead === "function";',
  "",
  "    if (modern) {",
  "        var body = await __bridgeBody(reqOrEvent);",
  "        var headers = Object.assign({}, reqOrEvent.headers);",
  '        delete headers["transfer-encoding"];',
  "        if (body.length > 0) {",
  '            headers["content-length"] = String(body.length);',
  "        } else {",
  '            delete headers["content-length"];',
  "        }",
  "",
  "        var input = await helpers_1.transformFromAwsRequest({",
  '            method: reqOrEvent.method || "GET",',
  "            path: reqOrEvent.url,",
  '            host: headers.host || "",',
  "            headers: headers,",
  "            body: body.length > 0 ? body : undefined,",
  "        });",
  "        var output = await query(input);",
  "        var outHeaders = Object.assign({}, output.headers);",
  '        delete outHeaders["transfer-encoding"];',
  '        delete outHeaders["connection"];',
  "        res.writeHead(output.statusCode, outHeaders);",
  "        res.end(output.body);",
  "        return;",
  "    }",
  "",
  "    var e = Object.assign({}, reqOrEvent);",
  "    if (Array.isArray(e.body)) {",
  "        e.body = Buffer.from(e.body);",
  '    } else if (typeof e.body === "string" && e.isBase64Encoded) {',
  '        e.body = Buffer.from(e.body, "base64");',
  "    }",
  "    return launcher(e);",
  "}",
  "",
  "module.exports = bridgeHandler;",
  "module.exports.default = bridgeHandler;",
  "module.exports.handler = bridgeHandler;",
  "module.exports.launcher = bridgeHandler;",
].join("\n");

function patchLauncherSource(source) {
  const rootInjection =
    'process.env.LAMBDA_TASK_ROOT = process.env.LAMBDA_TASK_ROOT || __dirname;';
  const directive = source.match(/^\s*["']use strict["'];?/);
  const withRoot = directive
    ? source.replace(directive[0], directive[0] + "\n" + rootInjection)
    : rootInjection + "\n" + source;

  return [
    withRoot,
    "",
    "/* --- added by build-vercel.js (Build Output API v3) --- */",
    BRIDGE_HANDLER,
    "",
  ].join("\n");
}

function copyDirRecursive(srcDir, destDir, skipFilter) {
  if (!fs.existsSync(srcDir)) return;
  fs.mkdirSync(destDir, { recursive: true });
  for (const entry of fs.readdirSync(srcDir, { withFileTypes: true })) {
    const srcPath = path.join(srcDir, entry.name);
    const destPath = path.join(destDir, entry.name);
    if (skipFilter && skipFilter(entry.name, srcPath)) continue;
    if (entry.isDirectory()) {
      copyDirRecursive(srcPath, destPath);
    } else if (entry.isFile()) {
      fs.copyFileSync(srcPath, destPath);
    }
  }
}

async function main() {
  const workPath = process.cwd();
  const outputDir = path.join(workPath, ".vercel", "output");
  const staticDir = path.join(outputDir, "static");
  const funcDir = path.join(outputDir, "functions", "api", "index.func");

  console.log("Cleaning .vercel/output...");
  fs.rmSync(outputDir, { recursive: true, force: true });
  fs.mkdirSync(staticDir, { recursive: true });
  fs.mkdirSync(funcDir, { recursive: true });

  // 1. Install Composer dependencies on Linux build containers
  const composerFile = path.join(workPath, "composer.json");
  if (fs.existsSync(composerFile)) {
    if (process.platform === "linux") {
      await runComposerInstall(workPath);
      await runComposerScripts(
        new FileBlob({ data: fs.readFileSync(composerFile) }),
        workPath
      );
    } else {
      console.log(
        "Skipping Composer install on non-Linux platform (" +
          process.platform +
          ")"
      );
    }
  }

  // 2. Collect PHP runtime files (normalized forward-slash paths)
  console.log("Preparing PHP runtime...");
  const rawLibPhpFiles = await libphp.getFiles();
  const runtimeFiles = {};
  for (const [rawName, fsPath] of Object.entries(rawLibPhpFiles)) {
    const name = rawName.replace(/\\/g, "/");
    if (
      name === "php/php-cgi" ||
      name === "php/php-fpm" ||
      name === "php/php-fpm.ini"
    ) {
      continue;
    }
    runtimeFiles[name] = new FileFsRef({ fsPath });
  }
  if (runtimeFiles["php/php"]) runtimeFiles["php/php"].mode = 33261; // 0755
  if (runtimeFiles["php/composer"]) runtimeFiles["php/composer"].mode = 33261;

  // Customize php.ini for production safety and memory balance
  if (runtimeFiles["php/php.ini"]) {
    let phpIni = fs.readFileSync(runtimeFiles["php/php.ini"].fsPath, "utf8");
    const userIniPath = path.join(workPath, "api", "php.ini");
    if (fs.existsSync(userIniPath)) {
      phpIni += "\n; [User]\n" + fs.readFileSync(userIniPath, "utf8");
    }
    phpIni += [
      "",
      "; [Production Overrides]",
      "display_errors=0",
      "error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED",
      "opcache.memory_consumption=64",
      "post_max_size=12M",
      "upload_max_filesize=12M",
      "",
    ].join("\n");
    runtimeFiles["php/php.ini"] = new FileBlob({ data: phpIni });
  }

  // 3. Collect and patch launcher files
  const launcherFiles = getLauncherFiles();
  for (const [rawName, fileRef] of Object.entries(launcherFiles)) {
    const name = rawName.replace(/\\/g, "/");
    if (name === "launcher.js") {
      const source = fs.readFileSync(fileRef.fsPath, "utf8");
      runtimeFiles["launcher.js"] = new FileBlob({
        data: patchLauncherSource(source),
        mode: fileRef.mode,
      });
    } else {
      runtimeFiles[name] = fileRef;
    }
  }

  // 4. Collect user application files (excluding heavy static assets and dev files)
  console.log("Collecting application files...");
  const harvested = rename(
    await glob("**", {
      cwd: workPath,
      ignore: [
        ".git/**",
        ".vercel/**",
        ".vscode/**",
        ".env*",
        "node_modules/**",
        "public/assets/**",
        "composer.phar",
        "docs/**",
        "tests/**",
        "Imagenes/**",
        "scripts/**",
        "now.json",
        ".nowignore",
        "vercel.json",
        ".vercelignore",
        "*.log",
        "*.tgz",
      ],
    }),
    (name) => path.posix.join("user", name.replace(/\\/g, "/"))
  );

  // 5. Write serverless function files into .vercel/output/functions/api/index.func
  console.log("Writing serverless function bundle...");
  await download({ ...harvested, ...runtimeFiles }, funcDir);

  for (const binRel of ["php/php", "php/composer"]) {
    const binPath = path.join(funcDir, binRel);
    if (fs.existsSync(binPath)) {
      fs.chmodSync(binPath, 0o755);
    }
  }

  const nodeVersion = await getNodeVersion(workPath);
  const vcConfig = {
    runtime: nodeVersion.runtime,
    handler: "launcher.js",
    launcherType: "Nodejs",
    architecture: "x86_64",
    memory: 512,
    maxDuration: 30,
    shouldAddHelpers: false,
    shouldAddSourcemapSupport: false,
    environment: {
      NOW_ENTRYPOINT: "api/index.php",
      NOW_PHP_DEV: "0",
    },
  };
  fs.writeFileSync(
    path.join(funcDir, ".vc-config.json"),
    JSON.stringify(vcConfig, null, 2) + "\n"
  );

  // 6. Copy static assets to .vercel/output/static
  console.log("Copying static assets...");
  copyDirRecursive(path.join(workPath, "public"), staticDir, (name, full) => {
    const rel = path.relative(path.join(workPath, "public"), full);
    return rel === "index.php" || rel === ".htaccess";
  });
  copyDirRecursive(
    path.join(workPath, "iconos_negocio"),
    path.join(staticDir, "iconos_negocio")
  );

  // 7. Write Build Output API v3 config.json
  const buildConfig = {
    version: 3,
    routes: [
      {
        src: "^/(favicon\\.ico|ads\\.txt|assets/.*|images/.*|css/.*|js/.*|sw\\.js|manifest\\.json|icons/.*|uploads/.*|webfonts/.*)$",
        headers: {
          "Cache-Control": "public, max-age=31536000, immutable",
        },
        continue: true,
      },
      {
        src: "^/(.*)$",
        headers: {
          "X-Content-Type-Options": "nosniff",
          "X-Frame-Options": "DENY",
          "Referrer-Policy": "strict-origin-when-cross-origin",
        },
        continue: true,
      },
      {
        handle: "filesystem",
      },
      {
        src: "^/api/(.*)$",
        dest: "/api/index?url=api/$1",
      },
      {
        src: "^/(.*)$",
        dest: "/api/index?url=$1",
      },
    ],
  };
  fs.writeFileSync(
    path.join(outputDir, "config.json"),
    JSON.stringify(buildConfig, null, 2) + "\n"
  );

  console.log("Vercel Build Output v3 ready.");
}

main().catch((err) => {
  console.error("Build failed:", err);
  process.exit(1);
});
