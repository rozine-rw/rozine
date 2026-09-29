"""Regenerate config/client-{source,risk}-manifest.json from git ls-files (authored = not generated/declaration-only)."""
import json, hashlib, subprocess, sys
from collections import OrderedDict
root = sys.argv[1]
sp = f'{root}/config/client-source-manifest.json'
rp = f'{root}/config/client-risk-manifest.json'
src = json.load(open(sp), object_pairs_hook=OrderedDict)
risk = json.load(open(rp), object_pairs_hook=OrderedDict)
tracked = subprocess.run(['git','-C',root,'ls-files','-co','--exclude-standard','resources/js'],capture_output=True,text=True).stdout.split()
tracked = sorted({p for p in tracked if p.endswith(('.ts','.tsx'))})
gen = set(src['generatedPaths']); decl = set(src['declarationOnlyPaths'])
missing_gen = [p for p in tracked if (p.startswith('resources/js/routes/') or p.startswith('resources/js/actions/') or p.startswith('resources/js/wayfinder/')) and p not in gen]
if missing_gen: print('WARNING new generated paths not listed:', missing_gen)
gen &= set(tracked); decl &= set(tracked)
authored = sorted(p for p in tracked if p not in gen and p not in decl and not p.endswith('.d.ts'))
src['generatedPaths'] = sorted(gen); src['declarationOnlyPaths'] = sorted(decl)
src['authoredExecutablePaths'] = authored
src['counts'] = OrderedDict([('totalTrackedSources', len(tracked)), ('generated', len(gen)), ('declarationOnly', len(decl)), ('authoredExecutable', len(authored))])
cs = hashlib.sha256(('\n'.join(authored)+'\n').encode()).hexdigest()
src['coverageSetSha256'] = cs
text = json.dumps(src, indent=4, ensure_ascii=False) + '\n'
open(sp,'w').write(text)
risk['sourceManifestSha256'] = hashlib.sha256(text.encode()).hexdigest()
risk['coverageSetSha256'] = cs
noncrit = set(risk.get('nonCriticalPaths', []))
risk['criticalPaths'] = [p for p in authored if p not in noncrit]
open(rp,'w').write(json.dumps(risk, indent=4, ensure_ascii=False) + '\n')
print(src['counts'])
