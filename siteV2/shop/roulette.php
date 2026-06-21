<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рулетка — MinecraftTimes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --bg:#08090c;
            --bg2:#0f1117;
            --surface:#161922;
            --surface2:#1c202b;
            --card-bg:#1e222e;
            --border:rgba(255,255,255,0.06);
            --border-light:rgba(255,255,255,0.1);
            --text:#f0f0f0;
            --text2:#8b8fa3;
            --text3:#5a5e6e;
            --accent:#2ecc71;
            --accent-dark:#27ae60;
            --accent-glow:rgba(46,204,113,0.4);
            --c-common:#95a5a6;
            --c-uncommon:#2ecc71;
            --c-rare:#3498db;
            --c-epic:#9b59b6;
            --c-legendary:#f39c12;
            --radius:12px;
            --radius-lg:20px;
        }
        html{height:100%}
        body{
            font-family:'Inter',system-ui,-apple-system,sans-serif;
            background:var(--bg);
            color:var(--text);
            min-height:100vh;
            display:flex;
            flex-direction:column;
            align-items:center;
            overflow-x:hidden;
            position:relative;
        }

        /* bg effects */
        .bg{position:fixed;inset:0;z-index:0;pointer-events:none;overflow:hidden}
        .bg-gradient{
            position:absolute;inset:0;
            background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(46,204,113,0.06),transparent),
                       radial-gradient(ellipse 50% 40% at 80% 100%,rgba(155,89,182,0.04),transparent),
                       radial-gradient(ellipse 50% 40% at 20% 100%,rgba(52,152,219,0.04),transparent);
        }
        .bg-grid{
            position:absolute;inset:0;
            background-image:linear-gradient(rgba(255,255,255,0.015) 1px,transparent 1px),
                             linear-gradient(90deg,rgba(255,255,255,0.015) 1px,transparent 1px);
            background-size:60px 60px;
        }
        .bg-orb{
            position:absolute;width:600px;height:600px;border-radius:50%;
            filter:blur(120px);opacity:0.15;animation:orbFloat 20s ease-in-out infinite;
        }
        .bg-orb:nth-child(3){background:var(--accent);top:-200px;right:-100px;animation-delay:0s}
        .bg-orb:nth-child(4){background:#9b59b6;bottom:-300px;left:-200px;animation-delay:-7s}
        .bg-orb:nth-child(5){background:#3498db;top:50%;left:50%;transform:translate(-50%,-50%);width:400px;height:400px;animation-delay:-14s}
        @keyframes orbFloat{
            0%,100%{transform:translate(0,0) scale(1)}
            33%{transform:translate(30px,-30px) scale(1.05)}
            66%{transform:translate(-20px,20px) scale(0.95)}
        }

        /* main */
        .main{position:relative;z-index:1;width:100%;max-width:960px;padding:24px 20px 40px;display:flex;flex-direction:column;align-items:center;gap:20px}

        /* case header */
        .case-header{
            width:100%;display:flex;align-items:center;justify-content:space-between;
            padding:16px 24px;background:var(--surface);border:1px solid var(--border);
            border-radius:var(--radius-lg);position:relative;overflow:hidden;
        }
        .case-header::before{
            content:'';position:absolute;inset:0;
            background:linear-gradient(90deg,transparent,rgba(46,204,113,0.03),transparent);
        }
        .case-header-left{display:flex;align-items:center;gap:16px}
        .case-icon{width:48px;height:48px;border-radius:10px;background:linear-gradient(135deg,var(--surface2),var(--card-bg));display:flex;align-items:center;justify-content:center;font-size:24px;border:1px solid var(--border)}
        .case-name{font-size:18px;font-weight:700;line-height:1.2}
        .case-meta{display:flex;align-items:center;gap:16px;font-size:13px;color:var(--text2)}
        .case-meta-item{display:flex;align-items:center;gap:6px}
        .case-meta-item .num{color:var(--text);font-weight:600}
        .case-price{font-size:16px;font-weight:700;color:var(--accent)}
        .case-badge{
            font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;
            padding:3px 10px;border-radius:20px;background:rgba(46,204,113,0.12);color:var(--accent);
            border:1px solid rgba(46,204,113,0.2);
        }

        /* payment bar */
        .payment-bar{
            width:100%;padding:12px 24px;background:rgba(46,204,113,0.06);
            border:1px solid rgba(46,204,113,0.12);border-radius:var(--radius);
            display:none;align-items:center;justify-content:center;gap:24px;
            font-size:13px;color:var(--text2);flex-wrap:wrap;
        }
        .payment-bar .label{color:var(--text2)}
        .payment-bar .val{color:var(--text);font-weight:600}
        .payment-bar .status-paid{color:var(--accent);font-weight:600;display:flex;align-items:center;gap:6px}
        .payment-bar .status-paid::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--accent);box-shadow:0 0 8px var(--accent-glow);animation:pulseDot 1.5s ease-in-out infinite}
        @keyframes pulseDot{0%,100%{opacity:1}50%{opacity:0.4}}

        .spins-progress{
            width:100%;padding:10px 24px;display:none;align-items:center;gap:16px;
            background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
        }
        .spins-progress-text{font-size:12px;color:var(--text2);white-space:nowrap}
        .spins-progress-text span{color:var(--text);font-weight:600}
        .spins-progress-bar-wrap{flex:1;height:6px;border-radius:3px;background:var(--surface2);overflow:hidden}
        .spins-progress-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--accent),#2ecc71);transition:width 0.5s ease}

        /* drop list */
        .drop-section{
            width:100%;background:var(--surface);border:1px solid var(--border);
            border-radius:var(--radius-lg);overflow:hidden;position:relative;
        }
        .drop-header{
            display:flex;align-items:center;justify-content:space-between;
            padding:14px 20px;cursor:pointer;user-select:none;
            border-bottom:1px solid transparent;transition:0.2s;
        }
        .drop-header:hover{background:rgba(255,255,255,0.02)}
        .drop-header-title{font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px}
        .drop-header-title .count{color:var(--text2);font-weight:400;font-size:12px}
        .drop-header-arrow{color:var(--text3);font-size:16px;transition:transform 0.3s}
        .drop-list-wrap{max-height:0;overflow:hidden;transition:max-height 0.4s ease}
        .drop-list-wrap.open{max-height:600px}
        .drop-list{padding:16px 20px;display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px}
        .drop-item{
            display:flex;align-items:center;gap:10px;padding:8px 10px;
            border-radius:8px;background:var(--card-bg);border:1px solid var(--border);
            transition:0.2s;position:relative;overflow:hidden;
        }
        .drop-item:hover{background:var(--surface2);transform:translateY(-1px)}
        .drop-item-rarity{
            position:absolute;left:0;top:0;bottom:0;width:3px;border-radius:3px 0 0 3px;
        }
        .drop-item-icon{width:36px;height:36px;object-fit:contain;image-rendering:pixelated;flex-shrink:0}
        .drop-item-info{display:flex;flex-direction:column;gap:2px;min-width:0}
        .drop-item-name{font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .drop-item-chance{font-size:10px;color:var(--text3)}
        .drop-section.collapsed .drop-header{border-bottom-color:var(--border)}
        .drop-section.collapsed .drop-header-arrow{transform:rotate(-90deg)}
        .drop-section:not(.collapsed) .drop-header-arrow{transform:rotate(0deg)}

        /* roulette */
        .roulette-section{width:100%;display:flex;flex-direction:column;align-items:center;gap:16px}
        .roulette-container{width:100%;position:relative;padding:0}
        .roulette-frame{
            position:relative;overflow:hidden;border-radius:var(--radius-lg);
            background:linear-gradient(180deg,#12141a,#0d0f14);
            border:1px solid var(--border-light);
            box-shadow:inset 0 2px 30px rgba(0,0,0,0.5),inset 0 0 60px rgba(0,0,0,0.15),0 0 40px rgba(0,0,0,0.4),0 0 80px rgba(0,0,0,0.2);
        }
        .roulette-frame::before{
            content:'';position:absolute;inset:0;
            background:linear-gradient(180deg,rgba(255,255,255,0.04) 0%,transparent 30%,transparent 70%,rgba(0,0,0,0.15) 100%);
            z-index:2;pointer-events:none;
        }
        .roulette-frame::after{
            content:'';position:absolute;inset:-3px;border-radius:23px;z-index:0;pointer-events:none;
            background:linear-gradient(180deg,rgba(255,255,255,0.05),transparent 50%,rgba(255,255,255,0.02));
        }
        .window{
            overflow:hidden;position:relative;height:200px;
            mask-image:linear-gradient(90deg,transparent 0%,#000 8%,#000 92%,transparent 100%);
            -webkit-mask-image:linear-gradient(90deg,transparent 0%,#000 8%,#000 92%,transparent 100%);
        }
        .window.spinning .card{filter:blur(1.5px)}
        .track{
            display:flex;align-items:center;height:100%;padding:0;
            transition:transform 8s cubic-bezier(0.12,0,0.02,1);will-change:transform;
        }

        .pointer-wrap{
            position:absolute;top:0;left:50%;transform:translateX(-50%);z-index:15;
            display:flex;flex-direction:column;align-items:center;pointer-events:none;
        }
        .pointer-arrow{
            width:0;height:0;
            border-left:12px solid transparent;border-right:12px solid transparent;
            border-top:16px solid var(--accent);filter:drop-shadow(0 0 10px var(--accent-glow));
        }
        .pointer-line{width:2px;height:calc(100% + 40px);background:linear-gradient(180deg,var(--accent),transparent);opacity:0.3}

        .card{
            width:165px;min-width:165px;max-width:165px;height:170px;margin:0 7px;flex-shrink:0;
            border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center;
            padding:12px;gap:2px;position:relative;overflow:hidden;
            border:2px solid var(--clr-border,#2a2e38);
            background:linear-gradient(180deg,var(--clr-bg-from,#1c202b),var(--clr-bg-to,#151821));
            box-shadow:var(--clr-shadow,none);
            transition:border-color 0.3s,box-shadow 0.3s;
            user-select:none;
        }
        .card::after{
            content:'';position:absolute;inset:0;border-radius:8px;
            background:linear-gradient(180deg,var(--clr-shine,rgba(255,255,255,0.02)),transparent 50%);
            pointer-events:none;
        }
        .card img{
            width:68px;height:68px;object-fit:contain;image-rendering:pixelated;
            margin:0;filter:drop-shadow(0 2px 6px rgba(0,0,0,0.4));
        }
        .card .item-name{
            font-size:11px;font-weight:600;text-align:center;line-height:1.2;
            color:var(--text);width:100%;overflow:hidden;text-overflow:ellipsis;
            display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;word-break:break-word;
        }

        /* rarity card themes */
        .c-common{--clr-border:#3d3f4a;--clr-bg-from:#1a1c24;--clr-bg-to:#151720;--clr-shadow:none;--clr-shine:rgba(149,165,166,0.02);--clr-text:#5a5e6e}
        .c-uncommon{--clr-border:rgba(46,204,113,0.3);--clr-bg-from:rgba(46,204,113,0.04);--clr-bg-to:#151821;--clr-shadow:inset 0 0 20px rgba(46,204,113,0.05);--clr-shine:rgba(46,204,113,0.04);--clr-text:rgba(46,204,113,0.5)}
        .c-rare{--clr-border:rgba(52,152,219,0.35);--clr-bg-from:rgba(52,152,219,0.05);--clr-bg-to:#151821;--clr-shadow:inset 0 0 25px rgba(52,152,219,0.07);--clr-shine:rgba(52,152,219,0.05);--clr-text:rgba(52,152,219,0.5)}
        .c-epic{--clr-border:rgba(155,89,182,0.4);--clr-bg-from:rgba(155,89,182,0.06);--clr-bg-to:#151821;--clr-shadow:inset 0 0 30px rgba(155,89,182,0.1);--clr-shine:rgba(155,89,182,0.06);--clr-text:rgba(155,89,182,0.5)}
        .c-legendary{
            --clr-border:rgba(243,156,18,0.5);--clr-bg-from:rgba(243,156,18,0.07);--clr-bg-to:#151821;
            --clr-shadow:inset 0 0 30px rgba(243,156,18,0.1),0 0 15px rgba(243,156,18,0.05);
            --clr-shine:rgba(243,156,18,0.08);--clr-text:rgba(243,156,18,0.6);
        }
        .c-legendary .item-name{background:linear-gradient(90deg,#f39c12,#e67e22);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

        .c-legendary::before{
            content:'';position:absolute;inset:-2px;border-radius:11px;
            background:linear-gradient(45deg,rgba(243,156,18,0.3),transparent,rgba(243,156,18,0.3),transparent);
            background-size:400% 400%;animation:legendaryBorder 3s ease infinite;z-index:-1;
        }
        @keyframes legendaryBorder{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}

        .card.winner{
            transform:scale(1.1);z-index:5;
            border-color:var(--clr-border,#2ecc71);
            box-shadow:var(--clr-shadow,none),0 0 30px var(--clr-border,#2ecc71);
            transition:transform 0.3s,box-shadow 0.3s,border-color 0.3s;
        }
        .card.winner::after{opacity:0.3}
        .c-legendary.winner{animation:winnerPulseL 0.7s ease-in-out 3}
        @keyframes winnerPulseL{
            0%,100%{box-shadow:inset 0 0 30px rgba(243,156,18,0.1),0 0 20px rgba(243,156,18,0.3),0 0 40px rgba(243,156,18,0.2)}
            50%{box-shadow:inset 0 0 30px rgba(243,156,18,0.15),0 0 40px rgba(243,156,18,0.5),0 0 70px rgba(243,156,18,0.3)}
        }

        @keyframes shake{
            0%,100%{transform:translate(0,0)}
            10%{transform:translate(-8px,4px)}
            20%{transform:translate(8px,-4px)}
            30%{transform:translate(-6px,6px)}
            40%{transform:translate(6px,-6px)}
            50%{transform:translate(-4px,2px)}
            60%{transform:translate(4px,-2px)}
            70%{transform:translate(-2px,1px)}
            80%{transform:translate(2px,-1px)}
            90%{transform:translate(-1px,1px)}
        }
        .shake{animation:shake 0.6s ease-out}

        /* spin area */
        .spin-area{display:flex;flex-direction:column;align-items:center;gap:10px;width:100%}
        .spin-btn{
            position:relative;padding:16px 48px;border:none;border-radius:50px;
            font-size:17px;font-weight:700;letter-spacing:0.5px;
            cursor:pointer;display:flex;align-items:center;gap:10px;
            background:linear-gradient(135deg,var(--accent),var(--accent-dark));
            color:#fff;box-shadow:0 4px 24px var(--accent-glow);
            transition:transform 0.2s,box-shadow 0.2s;
        }
        .spin-btn:hover:not(:disabled){
            transform:translateY(-2px) scale(1.02);
            box-shadow:0 6px 32px var(--accent-glow);
        }
        .spin-btn:active:not(:disabled){transform:translateY(0) scale(0.98)}
        .spin-btn:disabled{
            opacity:0.5;cursor:not-allowed;transform:none;
            box-shadow:0 2px 12px rgba(46,204,113,0.15);
        }
        .spin-btn .spinner{
            display:none;width:18px;height:18px;border:2px solid rgba(255,255,255,0.3);
            border-top-color:#fff;border-radius:50%;animation:spinBtn 0.6s linear infinite;
        }
        .spin-btn.loading .spinner{display:block}
        .spin-btn.loading .spin-btn-text{opacity:0.7}
        .spin-btn.loading .spin-btn-icon{display:none}
        @keyframes spinBtn{to{transform:rotate(360deg)}}
        .spin-btn.pulse{animation:btnPulse 1.5s ease-in-out infinite}
        @keyframes btnPulse{0%,100%{box-shadow:0 4px 24px var(--accent-glow)}50%{box-shadow:0 4px 40px var(--accent-glow),0 0 60px var(--accent-glow)}}

        .spin-hint{font-size:13px;color:var(--text3);text-align:center}
        .spin-skip{
            display:none;margin-top:4px;padding:6px 16px;font-size:11px;font-weight:500;
            border:none;border-radius:20px;background:var(--surface2);color:var(--text2);
            cursor:pointer;transition:0.2s;border:1px solid var(--border);
        }
        .spin-skip:hover{background:var(--card-bg);color:var(--text)}

        /* result overlay */
        .result-overlay{
            position:fixed;inset:0;z-index:200;
            display:none;align-items:center;justify-content:center;
            background:rgba(0,0,0,0.75);backdrop-filter:blur(16px);
            padding:20px;
        }
        .result-overlay.show{display:flex;animation:overlayIn 0.3s ease both}
        @keyframes overlayIn{0%{opacity:0;backdrop-filter:blur(0px)}100%{opacity:1;backdrop-filter:blur(16px)}}
        .result-overlay.show .result-card{animation:cardBurst 0.7s cubic-bezier(0.175,0.885,0.32,1.4) both}
        @keyframes cardBurst{
            0%{opacity:0;transform:scale(0.4) rotate(-3deg)}
            50%{transform:scale(1.05) rotate(0.5deg)}
            100%{opacity:1;transform:scale(1) rotate(0deg)}
        }

        .result-card{
            position:relative;background:var(--surface);border:1px solid var(--clr-border,rgba(255,255,255,0.1));
            border-radius:var(--radius-lg);padding:40px 48px;text-align:center;
            max-width:420px;width:100%;overflow:hidden;
        }
        .result-card::before{
            content:'';position:absolute;top:0;left:0;right:0;height:4px;
            background:var(--clr-accent,var(--accent));box-shadow:0 0 20px var(--clr-glow,rgba(46,204,113,0.3));
        }
        .result-badge{
            display:inline-block;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;
            padding:4px 14px;border-radius:20px;margin-bottom:16px;
            background:var(--clr-badge-bg,rgba(46,204,113,0.12));color:var(--clr-badge,var(--accent));
            border:1px solid var(--clr-border-light,rgba(46,204,113,0.2));
        }
        .result-img-wrap{
            position:relative;width:120px;height:120px;margin:0 auto 16px;
            display:flex;align-items:center;justify-content:center;
        }
        .result-img-wrap img{width:100%;height:100%;object-fit:contain;image-rendering:pixelated;position:relative;z-index:1;filter:drop-shadow(0 4px 12px rgba(0,0,0,0.4))}
        .result-glow{
            position:absolute;inset:-20px;border-radius:50%;
            background:var(--clr-glow-bg,rgba(46,204,113,0.15));filter:blur(30px);
            animation:resultGlow 2s ease-in-out infinite alternate;
        }
        @keyframes resultGlow{0%{opacity:0.6;transform:scale(0.9)}100%{opacity:1;transform:scale(1.1)}}
        .result-label{font-size:11px;color:var(--text3);text-transform:uppercase;letter-spacing:1px;margin-bottom:4px}
        .result-name{font-size:24px;font-weight:800;margin-bottom:4px;color:var(--clr-text,#fff)}
        .result-chance{font-size:13px;color:var(--text2);margin-bottom:16px}
        .result-rcon{
            font-size:12px;padding:8px 16px;border-radius:8px;margin-bottom:20px;
            display:inline-flex;align-items:center;gap:6px;
        }
        .result-rcon.ok{background:rgba(46,204,113,0.08);color:var(--accent);border:1px solid rgba(46,204,113,0.15)}
        .result-rcon.fail{background:rgba(231,76,60,0.08);color:#e74c3c;border:1px solid rgba(231,76,60,0.15)}
        .result-btn{
            padding:12px 32px;border:none;border-radius:50px;font-size:14px;font-weight:600;
            cursor:pointer;transition:0.2s;background:var(--surface2);color:var(--text);border:1px solid var(--border);
        }
        .result-btn:hover{background:var(--card-bg);border-color:var(--border-light)}

        /* result rarity themes */
        .r-none .result-card{--clr-border:rgba(255,255,255,0.1);--clr-accent:var(--text3);--clr-glow:rgba(90,94,110,0.2);--clr-badge-bg:rgba(90,94,110,0.12);--clr-badge:var(--text3);--clr-border-light:rgba(90,94,110,0.2);--clr-glow-bg:rgba(90,94,110,0.1);--clr-text:var(--text)}
        .r-common .result-card{--clr-border:rgba(149,165,166,0.2);--clr-accent:var(--c-common);--clr-glow:rgba(149,165,166,0.2);--clr-badge-bg:rgba(149,165,166,0.12);--clr-badge:var(--c-common);--clr-border-light:rgba(149,165,166,0.2);--clr-glow-bg:rgba(149,165,166,0.1);--clr-text:var(--c-common)}
        .r-uncommon .result-card{--clr-border:rgba(46,204,113,0.25);--clr-accent:var(--c-uncommon);--clr-glow:rgba(46,204,113,0.3);--clr-badge-bg:rgba(46,204,113,0.12);--clr-badge:var(--c-uncommon);--clr-border-light:rgba(46,204,113,0.2);--clr-glow-bg:rgba(46,204,113,0.15);--clr-text:var(--c-uncommon)}
        .r-rare .result-card{--clr-border:rgba(52,152,219,0.3);--clr-accent:var(--c-rare);--clr-glow:rgba(52,152,219,0.35);--clr-badge-bg:rgba(52,152,219,0.12);--clr-badge:var(--c-rare);--clr-border-light:rgba(52,152,219,0.2);--clr-glow-bg:rgba(52,152,219,0.18);--clr-text:var(--c-rare)}
        .r-epic .result-card{--clr-border:rgba(155,89,182,0.35);--clr-accent:var(--c-epic);--clr-glow:rgba(155,89,182,0.4);--clr-badge-bg:rgba(155,89,182,0.12);--clr-badge:var(--c-epic);--clr-border-light:rgba(155,89,182,0.2);--clr-glow-bg:rgba(155,89,182,0.2);--clr-text:var(--c-epic)}
        .r-legendary .result-card{
            --clr-border:rgba(243,156,18,0.4);--clr-accent:var(--c-legendary);--clr-glow:rgba(243,156,18,0.5);
            --clr-badge-bg:rgba(243,156,18,0.12);--clr-badge:var(--c-legendary);--clr-border-light:rgba(243,156,18,0.2);
            --clr-glow-bg:rgba(243,156,18,0.25);--clr-text:#f39c12;
        }

        /* confetti canvas */
        #confettiCanvas{
            position:fixed;inset:0;z-index:300;pointer-events:none;
            display:none;
        }

        /* responsive */
        @media(max-width:768px){
            .main{padding:20px 12px 24px}
            .case-header{padding:14px 16px;flex-direction:column;gap:10px;align-items:flex-start}
            .case-meta{flex-wrap:wrap}
            .card{width:130px;min-width:130px;max-width:130px;height:140px;margin:0 5px;padding:10px 8px}
            .card img{width:52px;height:52px}
            .card .item-name{font-size:10px}
            .window{height:160px}
            .roulette-frame{border-radius:var(--radius)}
            .drop-list{grid-template-columns:repeat(auto-fill,minmax(140px,1fr));padding:12px}
            .result-card{padding:28px 24px}
            .result-name{font-size:20px}
            .spin-btn{padding:14px 32px;font-size:15px}
        }
        @media(max-width:480px){
            .card{width:110px;min-width:110px;max-width:110px;height:120px;padding:8px 6px}
            .card img{width:44px;height:44px}
            .card .item-name{font-size:9px}
            .window{height:140px}
            .drop-list{grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:6px}
            .drop-item{padding:6px 8px}
            .drop-item-icon{width:28px;height:28px}
            .drop-item-name{font-size:10px}
        }
    </style>
</head>
<body>
    <div class="bg">
        <div class="bg-gradient"></div>
        <div class="bg-grid"></div>
        <div class="bg-orb"></div>
        <div class="bg-orb"></div>
        <div class="bg-orb"></div>
    </div>

    <div class="main">
        <div class="payment-bar" id="paymentBar"></div>
        <div class="spins-progress" id="spinsProgress" style="display:none;">
            <div class="spins-progress-text">Открытие <span id="spinsDone">0</span> из <span id="spinsTotal">1</span></div>
            <div class="spins-progress-bar-wrap"><div class="spins-progress-fill" id="spinsProgressFill" style="width:0%"></div></div>
        </div>

        <div class="case-header">
            <div class="case-header-left">
                <div class="case-icon">🎁</div>
                <div>
                    <div class="case-name" id="caseName">Загрузка...</div>
                    <div class="case-meta" id="caseMeta">
                        <span class="case-meta-item">📦 <span class="num" id="itemsCount">0</span> предметов</span>
                    </div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px">
                <span class="case-badge">🎰 КЕЙС</span>
                <span class="case-price" id="casePrice"></span>
            </div>
        </div>

        <div class="drop-section collapsed" id="dropSection">
            <div class="drop-header" onclick="toggleDropList()">
                <div class="drop-header-title">📋 Все предметы <span class="count" id="dropCount"></span></div>
                <span class="drop-header-arrow">▼</span>
            </div>
            <div class="drop-list-wrap" id="dropListWrap">
                <div class="drop-list" id="dropList"></div>
            </div>
        </div>

        <div class="roulette-section">
            <div class="roulette-container">
                <div class="roulette-frame">
                    <div class="pointer-wrap">
                        <div class="pointer-arrow"></div>
                        <div class="pointer-line"></div>
                    </div>
                    <div class="window" id="window">
                        <div class="track" id="track"></div>
                    </div>
                </div>
            </div>

            <div class="spin-area">
                <button class="spin-btn pulse" id="spinBtn" disabled>
                    <span class="spin-btn-icon">🔓</span>
                    <span class="spinner"></span>
                    <span class="spin-btn-text">Открыть кейс</span>
                </button>
                <div class="spin-hint" id="substatus">Загрузка...</div>
                <button class="spin-skip" id="skipBtn" onclick="skipAnimation()">⏭ Пропустить</button>
            </div>
        </div>
    </div>

    <div class="result-overlay" id="resultOverlay">
        <div class="result-card">
            <div class="result-badge" id="resultBadge">WIN</div>
            <div class="result-img-wrap">
                <div class="result-glow" id="resultGlow"></div>
                <img id="resultImage" src="" alt="">
            </div>
            <div class="result-label">Вам выпало</div>
            <div class="result-name" id="resultName">---</div>
            <div class="result-chance" id="resultChance">Шанс: 0%</div>
            <div class="result-rcon" id="resultRcon"></div>
            <button class="result-btn" id="resultBtn" onclick="closeResult()">Продолжить</button>
        </div>
    </div>

    <canvas id="confettiCanvas"></canvas>

    <script>
        let products = [];
        let paymentId = null;
        let caseInfo = null;
        let isPaid = false;
        let skipRequested = false;
        let resultTimeout = null;
        let currentWinner = null;
        let currentWinnerIndex = 0;
        let spinAudioCtx = null;
        let spinsLeft = 0;
        let spinsTotal = 1;
        let demoMode = false;

        function scheduleClick(ctx,t,vol){
            if(vol<0.008) return;
            try{
                const o1=ctx.createOscillator(),g1=ctx.createGain();
                o1.type='sine';
                o1.frequency.setValueAtTime(3200,t);
                o1.frequency.exponentialRampToValueAtTime(400,t+0.03);
                g1.gain.setValueAtTime(Math.min(vol,0.15),t);
                g1.gain.exponentialRampToValueAtTime(0.001,t+0.035);
                o1.connect(g1);g1.connect(ctx.destination);
                o1.start(t);o1.stop(t+0.035);

                const o2=ctx.createOscillator(),g2=ctx.createGain();
                o2.type='sine';
                o2.frequency.setValueAtTime(4800,t);
                o2.frequency.exponentialRampToValueAtTime(600,t+0.025);
                g2.gain.setValueAtTime(Math.min(vol*0.25,0.04),t);
                g2.gain.exponentialRampToValueAtTime(0.001,t+0.025);
                o2.connect(g2);g2.connect(ctx.destination);
                o2.start(t);o2.stop(t+0.025);
            }catch(e){}
        }

        function createSpinSound(totalDist){
            try{
                const ctx=new (window.AudioContext||window.webkitAudioContext)();
                if(ctx.state==='suspended') ctx.resume();
                const now=ctx.currentTime,dur=8,step=179,winC=480;

                const bx=t=>{
                    const u=1-t;
                    return 3*u*u*t*0.12+3*u*t*t*0.02+t*t*t;
                };
                const by=t=>3*t*t-2*t*t*t;
                const tForY=p=>{
                    let lo=0,hi=1;
                    for(let i=0;i<40;i++){
                        const m=(lo+hi)/2;
                        if(by(m)<p) lo=m; else hi=m;
                    }
                    return (lo+hi)/2;
                };

                const first=Math.ceil(winC/step);
                const last=Math.floor((totalDist+winC)/step);

                for(let n=first;n<=last;n++){
                    const dist=n*step-winC;
                    const p=Math.max(0,Math.min(1,dist/totalDist));
                    const t=tForY(p);
                    const time=bx(t)*dur;
                    const vol=Math.max(0.012,0.07*(1-p*0.7));
                    scheduleClick(ctx,now+time,vol);
                }

                setTimeout(()=>{
                    try{
                        const thud=ctx.createOscillator();
                        thud.type='sine';
                        thud.frequency.setValueAtTime(90,ctx.currentTime);
                        thud.frequency.exponentialRampToValueAtTime(25,ctx.currentTime+0.35);
                        const tg=ctx.createGain();
                        tg.gain.setValueAtTime(0.12,ctx.currentTime);
                        tg.gain.exponentialRampToValueAtTime(0.001,ctx.currentTime+0.35);
                        thud.connect(tg);tg.connect(ctx.destination);
                        thud.start();thud.stop(ctx.currentTime+0.35);
                    }catch(e){}
                },8050);

                spinAudioCtx=ctx;
            }catch(e){}
        }

        function stopSpinSound(){
            if(spinAudioCtx){
                try{spinAudioCtx.close()}catch(e){}
                spinAudioCtx=null;
            }
        }

        const MC_COLORS={
            '0':'#000000','1':'#0000AA','2':'#00AA00','3':'#00AAAA',
            '4':'#AA0000','5':'#AA00AA','6':'#FFAA00','7':'#AAAAAA',
            '8':'#555555','9':'#5555FF','a':'#55FF55','b':'#55FFFF',
            'c':'#FF5555','d':'#FF55FF','e':'#FFFF55','f':'#FFFFFF',
        };

        function formatMCName(name){
            if(!name) return name;
            let html=name
                .replace(/&#([0-9a-fA-F]{6})/g,'<span style="color:#$1">')
                .replace(/&([0-9a-fA-Fklmnor])/gi,(m,c)=>{
                    const l=c.toLowerCase();
                    if(MC_COLORS[l]) return `<span style="color:${MC_COLORS[l]}">`;
                    if(l==='l') return '<span style="font-weight:700">';
                    if(l==='m') return '<span style="text-decoration:line-through">';
                    if(l==='n') return '<span style="text-decoration:underline">';
                    if(l==='o') return '<span style="font-style:italic">';
                    if(l==='r') return '</span><span>';
                    return '';
                })
                .replace(/§([0-9a-fA-Fklmnor])/g,(m,c)=>{
                    const l=c.toLowerCase();
                    if(MC_COLORS[l]) return `<span style="color:${MC_COLORS[l]}">`;
                    if(l==='l') return '<span style="font-weight:700">';
                    if(l==='m') return '<span style="text-decoration:line-through">';
                    if(l==='n') return '<span style="text-decoration:underline">';
                    if(l==='o') return '<span style="font-style:italic">';
                    if(l==='r') return '</span><span>';
                    return '';
                });
            const open=(html.match(/<span/g)||[]).length;
            const close=(html.match(/<\/span>/g)||[]).length;
            if(open>close) html+='</span>'.repeat(open-close);
            return html;
        }

        const track = document.getElementById('track');
        const windowEl = document.getElementById('window');
        const btn = document.getElementById('spinBtn');
        const skipBtn = document.getElementById('skipBtn');
        const cardStep = 179;

        const urlParams = new URLSearchParams(window.location.search);
        const caseId = urlParams.get('id');
        paymentId = urlParams.get('payment_id');

        const RARITY_LABELS = {
            common:'COMMON',uncommon:'UNCOMMON',rare:'RARE',epic:'EPIC',legendary:'LEGENDARY'
        };
        const RARITY_CLASSES = ['c-common','c-uncommon','c-rare','c-epic','c-legendary'];
        const RARITY_RESULT = ['r-common','r-uncommon','r-rare','r-epic','r-legendary'];

        function formatChance(c){return c%1===0?c+'':c.toFixed(2)}

        function getRarity(chance){
            if(chance<=1) return 4;
            if(chance<=5) return 3;
            if(chance<=15) return 2;
            if(chance<=30) return 1;
            return 0;
        }

        function highlightWinner(index){
            const cards=track.querySelectorAll('.card');
            if(cards[index]) cards[index].classList.add('winner');
        }
        function clearWinnerHighlight(){
            track.querySelectorAll('.card.winner').forEach(c=>c.classList.remove('winner'));
        }

        function updateSpinProgress(){
            const done = spinsTotal - spinsLeft;
            document.getElementById('spinsDone').textContent = done;
            document.getElementById('spinsTotal').textContent = spinsTotal;
            document.getElementById('spinsProgressFill').style.width = (done / spinsTotal * 100) + '%';
            if (spinsTotal > 1) {
                document.getElementById('spinsProgress').style.display = 'flex';
            }
        }

        function updateSpinButtonText(){
            const btnText = btn.querySelector('.spin-btn-text');
            const btnIcon = btn.querySelector('.spin-btn-icon');
            if (spinsLeft <= 0 && !demoMode) {
                btnText.textContent = 'Вернуться в магазин';
                btnIcon.textContent = '🛒';
                btn.disabled = false;
                btn.onclick = function(){ window.location.href = '/index.php'; };
            } else if (demoMode) {
                btnText.textContent = 'Открыть кейс';
                btnIcon.textContent = '🔓';
                btn.onclick = function(){ spin(); };
            } else if (spinsLeft < spinsTotal) {
                btnText.textContent = `Открыть ещё (${spinsLeft})`;
                btnIcon.textContent = '🔓';
                btn.onclick = function(){ spin(); };
            } else {
                btnText.textContent = spinsTotal > 1 ? `Открыть кейс (${spinsTotal})` : 'Открыть кейс';
                btnIcon.textContent = '🔓';
                btn.onclick = function(){ spin(); };
            }
        }

        function weightedRandomPick(items){
            const total=items.reduce((s,i)=>s+i.weight,0);
            let r=Math.random()*total;
            for(const item of items){r-=item.weight;if(r<=0)return item}
            return items[items.length-1];
        }

        function getItemImageUrl(item){
            if(!item.item_id) return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2264%22 height=%2264%22%3E%3Crect fill=%22%23333%22 width=%2264%22 height=%2264%22/%3E%3C/svg%3E';
            const id = item.item_id.startsWith('minecraft:') ? item.item_id : 'minecraft:' + item.item_id;
            return `https://blocksitems.com/api/v1/items/${id}/icon?size=64`;
        }

        function buildTrack(winnerItem){
            track.innerHTML='';
            for(let i=0;i<100;i++){
                let item;
                if(winnerItem&&i>=80&&i<=84) item=winnerItem;
                else item=weightedRandomPick(products);
                const card=document.createElement('div');
                const r=getRarity(item.chance);
                card.className='card '+RARITY_CLASSES[r];
                const imgUrl=getItemImageUrl(item);
                card.innerHTML=`
                    <img src="${imgUrl}" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2264%22 height=%2264%22%3E%3Crect fill=%22%23333%22 width=%2264%22 height=%2264%22/%3E%3C/svg%3E'">
                    <span class="item-name">${formatMCName(item.name)}</span>
                `;
                track.appendChild(card);
            }
        }

        function buildDropList(items){
            const list=document.getElementById('dropList');
            document.getElementById('dropCount').textContent='— '+items.length+' предметов';
            list.innerHTML='';
            const sorted=[...items].sort((a,b)=>a.chance-b.chance);
            for(const item of sorted){
                const r=getRarity(item.chance);
                const el=document.createElement('div');
                el.className='drop-item';
                el.innerHTML=`
                    <div class="drop-item-rarity" style="background:var(--c-${['common','uncommon','rare','epic','legendary'][r]})"></div>
                    <img class="drop-item-icon" src="${getItemImageUrl(item)}" onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2264%22 height=%2264%22%3E%3Crect fill=%22%23333%22 width=%2264%22 height=%2264%22/%3E%3C/svg%3E'">
                    <div class="drop-item-info">
                        <span class="drop-item-name">${formatMCName(item.name)}</span>
                        <span class="drop-item-chance">${formatChance(item.chance)}%</span>
                    </div>
                `;
                list.appendChild(el);
            }
        }

        function toggleDropList(){
            const sec=document.getElementById('dropSection');
            const wrap=document.getElementById('dropListWrap');
            sec.classList.toggle('collapsed');
            if(!sec.classList.contains('collapsed')){
                wrap.classList.add('open');
            } else {
                wrap.classList.remove('open');
            }
        }

        async function init(){
            try{
                if(paymentId){
                    document.getElementById('substatus').innerText='Проверка платежа...';
                    const res=await fetch(`api.php?action=check_payment&payment_id=${paymentId}`);
                    const data=await res.json();
                    if(data.success&&data.status==='paid'&&!data.used){
                        isPaid=true;
                        demoMode=false;
                        caseInfo=data;
                        spinsLeft = data.spins_left ?? 1;
                        spinsTotal = data.spins_total ?? 1;
                        const bar=document.getElementById('paymentBar');
                        bar.style.display='flex';
                        bar.innerHTML=`
                            <span class="label">👤 <span class="val">${data.customer}</span></span>
                            <span class="label">📦 <span class="val">${data.case_name}</span></span>
                            <span class="status-paid">✓ Оплачен</span>
                        `;
                        updateSpinProgress();
                        const caseRes=await fetch(`api.php?case_id=${data.case_id}`);
                        const caseData=await caseRes.json();
                        if(caseData.success&&caseData.items){
                            products=caseData.items;
                            document.getElementById('caseName').textContent=data.case_name;
                            document.getElementById('itemsCount').textContent=products.length;
                            document.getElementById('casePrice').textContent=caseData.price+' ₽';
                            buildTrack(null);
                            buildDropList(products);
                            btn.disabled=false;
                            if (spinsTotal > 1) {
                                document.getElementById('substatus').innerText=`Осталось открытий: ${spinsLeft} из ${spinsTotal}`;
                            } else {
                                document.getElementById('substatus').innerText='Один бросок — одна попытка. Удачи!';
                            }
                            updateSpinButtonText();

                            // Восстановление после прерывания (обновление страницы во время спина)
                            if (data.pending_item) {
                                document.getElementById('substatus').innerText='🔄 Восстановление прерванного спина...';
                                btn.disabled = true;
                                try {
                                    const confirmRes = await fetch(`api.php?action=spin_case&payment_id=${paymentId}`);
                                    const confirmData = await confirmRes.json();
                                    if (confirmData.success) {
                                        spinsLeft = confirmData.spins_left ?? 0;
                                        spinsTotal = confirmData.spins_total ?? spinsTotal;
                                        updateSpinProgress();
                                        updateSpinButtonText();
                                        showResult(
                                            { name: confirmData.item.name, chance: 0 },
                                            confirmData.item,
                                            confirmData.rcon
                                        );
                                    } else {
                                        btn.disabled = false;
                                        updateSpinButtonText();
                                        document.getElementById('substatus').innerText='Не удалось восстановить. Попробуйте открыть заново.';
                                    }
                                } catch(e) {
                                    btn.disabled = false;
                                    updateSpinButtonText();
                                    document.getElementById('substatus').innerText='Ошибка восстановления.';
                                }
                            }
                        }
                    } else if(data.status==='resolved'){
                        document.getElementById('caseName').textContent='⏳ Прокрутка уже начата';
                        document.getElementById('substatus').innerText='Обновите страницу — спин будет восстановлен.';
                    } else if(data.used){
                        document.getElementById('caseName').textContent='❌ Все открытия использованы';
                        document.getElementById('substatus').innerText='Купите ещё кейс в магазине, чтобы продолжить.';
                    } else if(data.status==='pending'){
                        document.getElementById('caseName').textContent='⏳ Ожидание оплаты';
                        document.getElementById('substatus').innerText='Пожалуйста, оплатите кейс и обновите страницу.';
                    } else {
                        document.getElementById('caseName').textContent='❌ Платёж не найден';
                        document.getElementById('substatus').innerText='Проверьте ссылку или оплатите кейс в магазине.';
                    }
                } else if(caseId){
                    demoMode=true;
                    document.getElementById('caseName').textContent='🎰 Демо-рулетка';
                    document.getElementById('substatus').innerText='Режим просмотра — предметы не выдаются';
                    const res=await fetch(`api.php?case_id=${caseId}`);
                    const data=await res.json();
                    if(data.success&&data.items){
                        products=data.items;
                        document.getElementById('itemsCount').textContent=products.length;
                        document.getElementById('casePrice').textContent=data.price+' ₽';
                        buildTrack(null);
                        buildDropList(products);
                        btn.disabled=false;
                        updateSpinButtonText();
                        document.getElementById('substatus').innerText='Нажмите, чтобы попробовать!';
                    }
                } else {
                    document.getElementById('caseName').textContent='❌ Кейс не выбран';
                    document.getElementById('substatus').innerText='Выберите кейс в магазине.';
                }
            }catch(e){
                document.getElementById('caseName').textContent='❌ Ошибка загрузки';
                document.getElementById('substatus').innerText='Проверьте соединение с сервером.';
            }
        }

        async function spin(){
            btn.disabled=true;
            btn.classList.add('loading');
            btn.classList.remove('pulse');
            skipRequested=false;
            skipBtn.style.display='none';
            currentWinner=null;
            document.getElementById('resultOverlay').classList.remove('show');

            let winnerItem;

            if(paymentId&&isPaid){
                document.getElementById('substatus').innerText='🎲 Сервер выбирает предмет...';
                const resolveRes=await fetch(`api.php?action=resolve_spin&payment_id=${paymentId}`);
                const resolveData=await resolveRes.json();
                if(!resolveData.success){
                    document.getElementById('caseName').textContent='❌ '+(resolveData.response||'Ошибка');
                    document.getElementById('substatus').innerText='Попробуйте ещё раз.';
                    btn.disabled=false;btn.classList.remove('loading');btn.classList.add('pulse');
                    return;
                }
                winnerItem=resolveData.item;
            } else {
                winnerItem=weightedRandomPick(products);
            }

            currentWinner=winnerItem;
            buildTrack(winnerItem);

            track.style.transition='none';
            track.style.transform='translateX(0)';
            void track.offsetHeight;

            currentWinnerIndex=80+Math.floor(Math.random()*5);
            const stopPos=(currentWinnerIndex*cardStep)-(960/2)+(cardStep/2);
            const randomOffset=Math.floor(Math.random()*60)-30;
            const totalDist=stopPos+randomOffset;

            stopSpinSound();
            createSpinSound(totalDist);

            document.getElementById('substatus').innerText='🎰 Крутим...';
            windowEl.classList.add('spinning');

            track.style.transition='transform 8s cubic-bezier(0.12,0,0.02,1)';
            track.style.transform=`translateX(-${totalDist}px)`;

            skipBtn.style.display='block';

            const delay=paymentId&&isPaid?8500:8500;

            resultTimeout=setTimeout(()=>finishSpin(winnerItem,paymentId&&isPaid),delay);

            if(!skipRequested){
                setTimeout(()=>{
                    if(!skipRequested) document.getElementById('substatus').innerText='⏳ Замедляется...';
                },3200);
            }
        }

        function playWinSound(rarity){
            try{
                const ctx=new (window.AudioContext||window.webkitAudioContext)();
                const now=ctx.currentTime;
                if(rarity===4){
                    [523,659,784,1047].forEach((f,i)=>{
                        const o=ctx.createOscillator(),g=ctx.createGain();
                        o.type='sine';o.frequency.value=f;
                        g.gain.setValueAtTime(0.07,now+i*0.08);
                        g.gain.exponentialRampToValueAtTime(0.001,now+i*0.08+0.35);
                        o.connect(g);g.connect(ctx.destination);
                        o.start(now+i*0.08);o.stop(now+i*0.08+0.35);
                    });
                } else if(rarity===3){
                    [440,554,659].forEach((f,i)=>{
                        const o=ctx.createOscillator(),g=ctx.createGain();
                        o.type='sine';o.frequency.value=f;
                        g.gain.setValueAtTime(0.05,now+i*0.1);
                        g.gain.exponentialRampToValueAtTime(0.001,now+i*0.1+0.3);
                        o.connect(g);g.connect(ctx.destination);
                        o.start(now+i*0.1);o.stop(now+i*0.1+0.3);
                    });
                } else if(rarity===2){
                    const o=ctx.createOscillator(),g=ctx.createGain();
                    o.type='sine';o.frequency.setValueAtTime(440,now);
                    o.frequency.exponentialRampToValueAtTime(880,now+0.25);
                    g.gain.setValueAtTime(0.04,now);
                    g.gain.exponentialRampToValueAtTime(0.001,now+0.35);
                    o.connect(g);g.connect(ctx.destination);
                    o.start(now);o.stop(now+0.35);
                }
            }catch(e){}
        }

        async function finishSpin(winnerItem,paid){
            windowEl.classList.remove('spinning');
            stopSpinSound();

            const r=getRarity(winnerItem.chance);

            highlightWinner(currentWinnerIndex);
            if(r>=2){
                document.querySelector('.roulette-frame').classList.add('shake');
                playWinSound(r);
            }
            document.getElementById('substatus').innerText=r>=3?'🔥 РЕДКОСТЬ!':'⏳ Раскрытие...';

            await new Promise(r=>setTimeout(r,600));

            clearWinnerHighlight();
            document.querySelector('.roulette-frame').classList.remove('shake');
            skipBtn.style.display='none';
            btn.classList.remove('loading');

            if(paid){
                try{
                    const confirmRes=await fetch(`api.php?action=spin_case&payment_id=${paymentId}`);
                    const confirmData=await confirmRes.json();
                    if(confirmData.success){
                        spinsLeft = confirmData.spins_left ?? (spinsLeft - 1);
                        spinsTotal = confirmData.spins_total ?? spinsTotal;
                        updateSpinProgress();
                        showResult(winnerItem,confirmData.item,confirmData.rcon);
                    } else {
                        showResult(winnerItem,winnerItem,{success:false,response:confirmData.response||'Ошибка'});
                    }
                }catch(e){
                    showResult(winnerItem,winnerItem,{success:false,response:'Ошибка сервера'});
                }
            } else {
                showResult(winnerItem,winnerItem,{success:true,response:'Демо-режим'});
            }
        }

        function showResult(winnerItem,item,rcon){
            const r=getRarity(winnerItem.chance);
            const overlay=document.getElementById('resultOverlay');
            const rc=['r-common','r-uncommon','r-rare','r-epic','r-legendary'][r];
            const rarityName=RARITY_LABELS[['common','uncommon','rare','epic','legendary'][r]];

            overlay.className='result-overlay show '+rc;
            document.getElementById('resultBadge').textContent=rarityName;
            document.getElementById('resultImage').src=getItemImageUrl(winnerItem);
            document.getElementById('resultImage').onerror=function(){this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2264%22 height=%2264%22%3E%3Crect fill=%22%23333%22 width=%2264%22 height=%2264%22/%3E%3C/svg%3E'};
            const amountStr=item.amount>1?` x${item.amount}`:'';
            document.getElementById('resultName').innerHTML=formatMCName(winnerItem.name)+amountStr;
            document.getElementById('resultChance').textContent=`Шанс выпадения: ${formatChance(winnerItem.chance)}%`;

            const rconEl=document.getElementById('resultRcon');
            if(paid){
                if(rcon.success){
                    rconEl.className='result-rcon ok';
                    rconEl.innerHTML=`✅ ${formatMCName(item.name)} выдан на сервер`;
                } else {
                    rconEl.className='result-rcon fail';
                    rconEl.innerHTML=`❌ Ошибка выдачи: ${rcon.response}`;
                }
            } else {
                rconEl.className='result-rcon ok';
                rconEl.innerHTML=`🔓 Демо-режим — купите кейс, чтобы получить предмет`;
            }

            document.getElementById('resultBtn').textContent=r>=3?'🎉 Забрать':'Продолжить';
            document.getElementById('substatus').innerText='🎉 Выигрыш!';

            if(r>=2) launchConfetti(r);

            btn.disabled=false;
            btn.classList.add('pulse');
        }

        function skipAnimation(){
            if(resultTimeout&&!skipRequested){
                skipRequested=true;
                clearTimeout(resultTimeout);
                resultTimeout=null;
                stopSpinSound();
                clearWinnerHighlight();
                document.querySelector('.roulette-frame').classList.remove('shake');
                skipBtn.style.display='none';
                windowEl.classList.remove('spinning');
                btn.classList.remove('loading');
                if(currentWinner){
                    finishSpin(currentWinner,paymentId&&isPaid);
                }
            }
        }

        function closeResult(){
            document.getElementById('resultOverlay').classList.remove('show');
            btn.classList.remove('loading');
            if (!demoMode && spinsLeft > 0) {
                btn.disabled = false;
                btn.classList.add('pulse');
                updateSpinButtonText();
                document.getElementById('substatus').innerText = `Осталось открытий: ${spinsLeft} из ${spinsTotal}`;
            } else if (!demoMode && spinsLeft <= 0) {
                updateSpinButtonText();
                document.getElementById('substatus').innerText = 'Все открытия использованы!';
            } else {
                btn.disabled = false;
                btn.classList.add('pulse');
                document.getElementById('substatus').innerText = 'Нажмите, чтобы попробовать!';
            }
        }

        let confetti=null;

        function initConfetti(){
            const canvas=document.getElementById('confettiCanvas');
            const ctx=canvas.getContext('2d');
            let particles=[];
            let running=false;

            function resize(){
                canvas.width=window.innerWidth;
                canvas.height=window.innerHeight;
            }
            window.addEventListener('resize',resize);
            resize();

            return {
                launch(colors,count=80){
                    canvas.style.display='block';
                    particles=[];
                    const cx=canvas.width/2;
                    const cy=canvas.height/3;
                    for(let i=0;i<count;i++){
                        const angle=Math.random()*Math.PI*2;
                        const speed=Math.random()*12+4;
                        particles.push({
                            x:cx,y:cy,
                            vx:Math.cos(angle)*speed,
                            vy:Math.sin(angle)*speed-6,
                            size:Math.random()*8+4,
                            color:colors[Math.floor(Math.random()*colors.length)],
                            life:1,
                            decay:Math.random()*0.008+0.004,
                            rotation:Math.random()*360,
                            rotSpeed:(Math.random()-0.5)*12,
                            gravity:0.15+Math.random()*0.1,
                            wobble:Math.random()*Math.PI*2,
                            wobbleSpeed:Math.random()*0.05+0.02,
                        });
                    }
                    if(!running){
                        running=true;
                        animate();
                    }
                },
                stop(){
                    particles=[];
                    running=false;
                    ctx.clearRect(0,0,canvas.width,canvas.height);
                    canvas.style.display='none';
                }
            };

            function animate(){
                ctx.clearRect(0,0,canvas.width,canvas.height);
                let alive=false;
                for(const p of particles){
                    p.wobble+=p.wobbleSpeed;
                    p.vy+=p.gravity;
                    p.vx+=Math.sin(p.wobble)*0.1;
                    p.x+=p.vx;
                    p.y+=p.vy;
                    p.vx*=0.99;
                    p.life-=p.decay;
                    p.rotation+=p.rotSpeed;
                    if(p.life>0&&p.y<canvas.height+50){
                        alive=true;
                        ctx.save();
                        ctx.translate(p.x,p.y);
                        ctx.rotate(p.rotation*Math.PI/180);
                        ctx.globalAlpha=Math.max(0,p.life);
                        ctx.fillStyle=p.color;
                        ctx.shadowColor=p.color;
                        ctx.shadowBlur=4;
                        ctx.fillRect(-p.size/2,-p.size/4,p.size,p.size/2);
                        ctx.restore();
                    }
                }
                if(alive){
                    requestAnimationFrame(animate);
                } else {
                    running=false;
                    ctx.clearRect(0,0,canvas.width,canvas.height);
                    canvas.style.display='none';
                }
            }
        }

        function launchConfetti(rarity){
            if(!confetti) confetti=initConfetti();
            const palettes={
                2:['#3498db','#2980b9','#85c1e9','#5dade2'],
                3:['#9b59b6','#8e44ad','#c39bd3','#af7ac5'],
                4:['#f39c12','#e67e22','#f1c40f','#f7dc6f','#d4ac0d'],
            };
            const colors=palettes[rarity]||palettes[2];
            const count=rarity===4?120:rarity===3?80:50;
            confetti.launch(colors,count);
        }

        init();
    </script>
</body>
</html>
