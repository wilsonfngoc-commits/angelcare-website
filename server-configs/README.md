# server-configs — 伺服器設定快照（脫敏版）
更新：2026-09-26（Wilson GO「proceed 1-3」）

- wp-config.redacted.php／nginx／docker：自 production 伺服器採集，DB 密碼＋全部 KEY/SALT 已換 REDACTED
- staging/docker-compose.redacted.yml：本機 VM staging 容器定義（密碼已脫敏）
- 原版（未脫敏）wp-config backup：Cloudflare R2 `wilsonfngoc-backups/config/<site>/`（私桶）
- 還原順序參考：R2 db/<site>/（DB dump）→ R2 files/<site>-wp-content-*.tar.gz（外掛/主題/uploads）→ 本目錄 config（改返真實密碼）→ R2 media/<site>/uploads（圖片影片增量鏡像）
- 定期備份 job：site-db-offsite.sh（02:40）＋ site-media-offsite.sh（04:10，週日加全檔 tar）
