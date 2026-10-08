# Indeks Aturan Proyek

> Sebelum masuk mode rencana atau membuat/mengubah file apa pun, baca setiap file aturan yang glob-nya mencakup path dalam cakupan, lalu jalankan `grep -rin 'keyword' .ai/rules` untuk menangkap yang lolos dari pencocokan path.

| Area | Cakupan path | File aturan |
| --- | --- | --- |
| Pengujian Pest 5 + eksekusi paralel/TIA | `tests/**`, `app/**`, `routes/**`, `database/**`, `config/**` | @.ai/rules/pest.md |
