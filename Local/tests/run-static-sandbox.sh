#!/bin/sh
# Solo inspección estática. No ejecuta PHP/SQL ni instala dependencias.
set -eu
runner_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
source_dir=$(realpath "${1:-$runner_dir/../ProyectoBanca}")
node_binary=$(realpath "${NODE_BINARY:-/usr/bin/node}")
test -x "$node_binary"
test -f "$source_dir/seguridad.php"
exec timeout --kill-after=2s 20s prlimit --as=2147483648 --cpu=5 --nproc=64 --fsize=1048576 --nofile=64 -- \
 bwrap --unshare-all --unshare-user --unshare-cgroup --disable-userns --die-with-parent --new-session --cap-drop ALL \
 --ro-bind /usr /usr --symlink usr/lib /lib --symlink usr/lib64 /lib64 \
 --proc /proc --remount-ro /proc --dir /dev --ro-bind /dev/null /dev/null --ro-bind /dev/urandom /dev/urandom \
 --dir /toolchain --ro-bind "$node_binary" /toolchain/node \
 --ro-bind "$source_dir" /source \
 --dir /runner --ro-bind "$runner_dir/qa-regresion.cjs" /runner/qa-regresion.cjs \
 --size 16777216 --tmpfs /scratch --chdir /scratch --clearenv \
 --setenv PATH /toolchain:/usr/bin --setenv HOME /scratch --setenv TMPDIR /scratch --setenv XDG_CACHE_HOME /scratch/cache \
 --remount-ro / -- /toolchain/node --jitless --max-old-space-size=96 /runner/qa-regresion.cjs /source
