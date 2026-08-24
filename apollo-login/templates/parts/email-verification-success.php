<?php
/**
 * Email verification success — standalone "Conta Confirmada" page.
 *
 * @package Apollo\Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Apollo::Rio — Conta Confirmada</title>
  <script src="https://cdn.apollo.rio.br/v1.0.0/js/gsap/gsap.min.js" fetchpriority="high"></script>
  <script src="https://assets.apollo.rio.br/bg/space-inverted/space-inverted.js?v=001"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap');

    :root {
      --bg: #ffffff;
      --txt-heading: #181818;
      --txt-color: #6e6e6e;
      --accent: #181818;
      --shadow-glass: rgba(0, 0, 0, 0.05) 0px 0px 0px 1px, 0 450px 700px rgba(0,0,0,.15), inset 0 1px 1px rgba(255,255,255,0.8), inset 0 -1px 1px rgba(0,0,0,0.03);
      --r-lg: 32px;
    }
    
    * { box-sizing: border-box; }

    body { 
      margin: 0; 
      padding: 0; 
      height: 100dvh; 
      width: 100dvw;
      overflow: hidden; 
      background: linear-gradient(6deg, rgba(0,0,0,.075), transparent); 
      display: flex; 
      justify-content: center; 
      align-items: center; 
      -webkit-font-smoothing: antialiased;
    }
    .apollo-sb-y, .apollo-sb-x { display: none!important;}
    /* -------------------------------------------------------------
       SPACE BACKGROUND (PARALLAX PIXEL STARS - INVERTED TO BLACK)
       ------------------------------------------------------------- */
    #stars, #stars2, #stars3 {
      position: absolute; top: 0; left: 0; right: 0; bottom: 0;
      width: 100%; height: 100%; z-index: 0; pointer-events: none;
      opacity: 0.6; /* Softened slightly so it doesn't overpower the text */
    }
    
    /* Using a condensed version of the box-shadows for performance while keeping the effect */
    #stars {
      width: 1px; height: 1px; background: transparent;
      box-shadow: 72px 1640px #000, 1009px 365px #000, 227px 892px #000, 1340px 1142px #000, 309px 930px #000, 170px 560px #000, 749px 265px #000, 907px 983px #000, 863px 490px #000, 846px 1933px #000, 1693px 648px #000, 244px 369px #000, 265px 654px #000, 1607px 975px #000, 1960px 408px #000, 1664px 434px #000, 210px 1237px #000, 726px 1812px #000, 1426px 1046px #000, 1301px 1288px #000, 1642px 1822px #000, 986px 510px #000, 1104px 1748px #000, 53px 791px #000, 709px 667px #000, 1763px 1350px #000, 1439px 20px #000, 204px 1136px #000, 1855px 1878px #000, 132px 1014px #000, 1419px 185px #000, 296px 1130px #000, 1927px 114px #000, 1001px 1222px #000, 1743px 1943px #000, 1325px 132px #000, 1594px 1995px #000, 606px 671px #000, 391px 1657px #000, 1674px 1082px #000, 154px 394px #000, 1703px 142px #000, 335px 1608px #000, 1038px 1062px #000, 1832px 607px #000, 1383px 1269px #000, 1098px 273px #000, 1814px 809px #000, 1460px 1893px #000, 1584px 1456px #000, 1595px 1146px #000, 282px 105px #000, 1102px 1694px #000, 1735px 367px #000, 908px 830px #000, 1419px 15px #000, 1259px 1541px #000, 435px 541px #000, 943px 530px #000, 101px 1569px #000, 1402px 732px #000, 154px 1076px #000, 604px 1393px #000, 1641px 58px #000, 601px 133px #000, 817px 129px #000, 357px 273px #000, 220px 369px #000, 399px 357px #000, 1672px 996px #000, 241px 222px #000, 560px 997px #000, 32px 37px #000, 420px 1335px #000, 273px 1504px #000, 706px 306px #000, 1079px 1807px #000, 1029px 164px #000, 430px 771px #000, 1482px 15px #000, 1883px 1354px #000, 1115px 1408px #000, 918px 1915px #000, 1910px 522px #000, 151px 755px #000, 1191px 1428px #000, 1408px 64px #000, 520px 1467px #000, 1319px 1398px #000, 1115px 323px #000, 615px 706px #000, 639px 1468px #000, 1644px 279px #000, 1467px 347px #000, 704px 372px #000, 309px 1801px #000, 366px 1879px #000, 1282px 688px #000, 1266px 124px #000, 718px 499px #000, 1724px 1916px #000, 1172px 755px #000, 478px 565px #000, 869px 193px #000, 287px 1230px #000, 603px 456px #000, 211px 1469px #000, 781px 1583px #000, 1226px 774px #000, 345px 802px #000, 624px 184px #000, 1726px 1356px #000;
      animation: animStar 50s linear infinite;
    }
    #stars:after {
      content: " "; position: absolute; top: 2000px; width: 1px; height: 1px; background: transparent;
      box-shadow: 72px 1640px #000, 1009px 365px #000, 227px 892px #000, 1340px 1142px #000, 309px 930px #000, 170px 560px #000, 749px 265px #000, 907px 983px #000, 863px 490px #000, 846px 1933px #000, 1693px 648px #000, 244px 369px #000, 265px 654px #000, 1607px 975px #000, 1960px 408px #000, 1664px 434px #000, 210px 1237px #000, 726px 1812px #000, 1426px 1046px #000, 1301px 1288px #000, 1642px 1822px #000, 986px 510px #000, 1104px 1748px #000, 53px 791px #000, 709px 667px #000, 1763px 1350px #000, 1439px 20px #000, 204px 1136px #000, 1855px 1878px #000, 132px 1014px #000, 1419px 185px #000, 296px 1130px #000, 1927px 114px #000, 1001px 1222px #000, 1743px 1943px #000, 1325px 132px #000, 1594px 1995px #000, 606px 671px #000, 391px 1657px #000, 1674px 1082px #000, 154px 394px #000, 1703px 142px #000, 335px 1608px #000, 1038px 1062px #000, 1832px 607px #000, 1383px 1269px #000, 1098px 273px #000, 1814px 809px #000, 1460px 1893px #000, 1584px 1456px #000, 1595px 1146px #000, 282px 105px #000, 1102px 1694px #000, 1735px 367px #000, 908px 830px #000, 1419px 15px #000, 1259px 1541px #000, 435px 541px #000, 943px 530px #000, 101px 1569px #000, 1402px 732px #000, 154px 1076px #000, 604px 1393px #000, 1641px 58px #000, 601px 133px #000, 817px 129px #000, 357px 273px #000, 220px 369px #000, 399px 357px #000, 1672px 996px #000, 241px 222px #000, 560px 997px #000, 32px 37px #000, 420px 1335px #000, 273px 1504px #000, 706px 306px #000, 1079px 1807px #000, 1029px 164px #000, 430px 771px #000, 1482px 15px #000, 1883px 1354px #000, 1115px 1408px #000, 918px 1915px #000, 1910px 522px #000, 151px 755px #000, 1191px 1428px #000, 1408px 64px #000, 520px 1467px #000, 1319px 1398px #000, 1115px 323px #000, 615px 706px #000, 639px 1468px #000, 1644px 279px #000, 1467px 347px #000, 704px 372px #000, 309px 1801px #000, 366px 1879px #000, 1282px 688px #000, 1266px 124px #000, 718px 499px #000, 1724px 1916px #000, 1172px 755px #000, 478px 565px #000, 869px 193px #000, 287px 1230px #000, 603px 456px #000, 211px 1469px #000, 781px 1583px #000, 1226px 774px #000, 345px 802px #000, 624px 184px #000, 1726px 1356px #000;
    }
    
    #stars2 {
      width: 2px; height: 2px; background: transparent;
      box-shadow: 1819px 747px #000, 1851px 1363px #000, 1832px 1100px #000, 1745px 450px #000, 1436px 1292px #000, 1638px 1566px #000, 1768px 1633px #000, 990px 777px #000, 1724px 1253px #000, 1078px 101px #000, 1654px 691px #000, 1650px 825px #000, 1839px 710px #000, 722px 13px #000, 1215px 1647px #000, 1716px 21px #000, 1262px 1396px #000, 1496px 17px #000, 469px 1600px #000, 1292px 27px #000, 937px 1822px #000, 502px 1224px #000, 1152px 533px #000, 1711px 248px #000, 1280px 1429px #000, 423px 1616px #000, 891px 1379px #000, 1849px 1306px #000, 339px 1543px #000, 1688px 1582px #000, 1527px 1400px #000, 925px 371px #000, 735px 1727px #000, 1129px 1750px #000, 1302px 176px #000, 202px 421px #000, 279px 1724px #000, 1018px 1728px #000, 1634px 1842px #000, 24px 122px #000, 1375px 808px #000, 136px 608px #000, 1653px 1648px #000, 1463px 827px #000, 562px 847px #000, 1359px 1208px #000, 132px 81px #000, 1646px 209px #000, 1150px 276px #000, 131px 687px #000, 1995px 1584px #000, 570px 899px #000, 1524px 459px #000, 1698px 1905px #000, 135px 1219px #000, 846px 724px #000, 344px 260px #000, 416px 1092px #000, 673px 1631px #000, 618px 1448px #000, 67px 1004px #000, 254px 1916px #000, 276px 1138px #000, 1388px 1903px #000, 1934px 1455px #000, 1579px 1732px #000, 448px 1567px #000, 671px 1029px #000, 584px 1995px #000, 1007px 732px #000, 932px 244px #000, 1232px 333px #000, 124px 1946px #000, 710px 1278px #000, 1594px 1435px #000, 999px 1969px #000, 815px 938px #000, 863px 300px #000, 1627px 61px #000, 1392px 955px #000, 1760px 865px #000, 852px 447px #000, 1360px 1327px #000, 1374px 610px #000, 1773px 1039px #000, 1698px 658px #000, 848px 851px #000, 75px 1906px #000;
      animation: animStar 100s linear infinite;
    }
    #stars2:after {
      content: " "; position: absolute; top: 2000px; width: 2px; height: 2px; background: transparent;
      box-shadow: 1819px 747px #000, 1851px 1363px #000, 1832px 1100px #000, 1745px 450px #000, 1436px 1292px #000, 1638px 1566px #000, 1768px 1633px #000, 990px 777px #000, 1724px 1253px #000, 1078px 101px #000, 1654px 691px #000, 1650px 825px #000, 1839px 710px #000, 722px 13px #000, 1215px 1647px #000, 1716px 21px #000, 1262px 1396px #000, 1496px 17px #000, 469px 1600px #000, 1292px 27px #000, 937px 1822px #000, 502px 1224px #000, 1152px 533px #000, 1711px 248px #000, 1280px 1429px #000, 423px 1616px #000, 891px 1379px #000, 1849px 1306px #000, 339px 1543px #000, 1688px 1582px #000, 1527px 1400px #000, 925px 371px #000, 735px 1727px #000, 1129px 1750px #000, 1302px 176px #000, 202px 421px #000, 279px 1724px #000, 1018px 1728px #000, 1634px 1842px #000, 24px 122px #000, 1375px 808px #000, 136px 608px #000, 1653px 1648px #000, 1463px 827px #000, 562px 847px #000, 1359px 1208px #000, 132px 81px #000, 1646px 209px #000, 1150px 276px #000, 131px 687px #000, 1995px 1584px #000, 570px 899px #000, 1524px 459px #000, 1698px 1905px #000, 135px 1219px #000, 846px 724px #000, 344px 260px #000, 416px 1092px #000, 673px 1631px #000, 618px 1448px #000, 67px 1004px #000, 254px 1916px #000, 276px 1138px #000, 1388px 1903px #000, 1934px 1455px #000, 1579px 1732px #000, 448px 1567px #000, 671px 1029px #000, 584px 1995px #000, 1007px 732px #000, 932px 244px #000, 1232px 333px #000, 124px 1946px #000, 710px 1278px #000, 1594px 1435px #000, 999px 1969px #000, 815px 938px #000, 863px 300px #000, 1627px 61px #000, 1392px 955px #000, 1760px 865px #000, 852px 447px #000, 1360px 1327px #000, 1374px 610px #000, 1773px 1039px #000, 1698px 658px #000, 848px 851px #000, 75px 1906px #000;
    }
    
    #stars3 {
      width: 3px; height: 3px; background: transparent;
      box-shadow: 1229px 452px #000, 1963px 1052px #000, 495px 1386px #000, 1254px 988px #000, 1180px 1803px #000, 237px 1395px #000, 1093px 488px #000, 219px 1012px #000, 758px 981px #000, 98px 350px #000, 1419px 1434px #000, 1215px 1393px #000, 1023px 28px #000, 470px 191px #000, 1955px 1017px #000, 1687px 113px #000, 1288px 143px #000, 1903px 807px #000, 1221px 1143px #000, 1688px 1937px #000, 1588px 1975px #000, 1992px 808px #000, 1696px 1091px #000, 937px 1805px #000, 541px 969px #000, 759px 847px #000, 44px 1454px #000, 652px 1043px #000, 1887px 1486px #000, 499px 1287px #000, 1931px 1874px #000, 1763px 950px #000, 1840px 1597px #000, 1636px 1896px #000, 1317px 1652px #000, 874px 1055px #000, 908px 670px #000, 1928px 431px #000, 1667px 1967px #000, 1653px 886px #000, 1781px 409px #000, 1657px 946px #000, 1921px 455px #000, 873px 1620px #000, 1077px 685px #000, 1844px 1748px #000, 1973px 1765px #000, 1492px 875px #000, 1363px 240px #000, 1101px 1018px #000;
      animation: animStar 150s linear infinite;
    }
    #stars3:after {
      content: " "; position: absolute; top: 2000px; width: 3px; height: 3px; background: transparent;
      box-shadow: 1229px 452px #000, 1963px 1052px #000, 495px 1386px #000, 1254px 988px #000, 1180px 1803px #000, 237px 1395px #000, 1093px 488px #000, 219px 1012px #000, 758px 981px #000, 98px 350px #000, 1419px 1434px #000, 1215px 1393px #000, 1023px 28px #000, 470px 191px #000, 1955px 1017px #000, 1687px 113px #000, 1288px 143px #000, 1903px 807px #000, 1221px 1143px #000, 1688px 1937px #000, 1588px 1975px #000, 1992px 808px #000, 1696px 1091px #000, 937px 1805px #000, 541px 969px #000, 759px 847px #000, 44px 1454px #000, 652px 1043px #000, 1887px 1486px #000, 499px 1287px #000, 1931px 1874px #000, 1763px 950px #000, 1840px 1597px #000, 1636px 1896px #000, 1317px 1652px #000, 874px 1055px #000, 908px 670px #000, 1928px 431px #000, 1667px 1967px #000, 1653px 886px #000, 1781px 409px #000, 1657px 946px #000, 1921px 455px #000, 873px 1620px #000, 1077px 685px #000, 1844px 1748px #000, 1973px 1765px #000, 1492px 875px #000, 1363px 240px #000, 1101px 1018px #000;
    }
    
    @keyframes animStar {
      from { transform: translateY(0px); }
      to   { transform: translateY(-2000px); }
    }

    /* -------------------------------------------------------------
       MOBILE FIRST GLASS UI
       ------------------------------------------------------------- */
    #app-container {
      width: 80%;
      max-width: 440px;
      padding: 48px 26px 10px;
      height: auto;
      /* LIQUID GLASS EFFECT - LIGHT */
      background: rgba(255,255,255,.15)!important;
      backdrop-filter: blur(2px) saturate(180%) brightness(1.5);
      -webkit-backdrop-filter: blur(2px) saturate(180%) brightness(1.5);
      border: 1px solid rgba(255, 255, 255, 0.8);
      border-radius: var(--r-lg);
      box-shadow: var(--shadow-glass);
      position: relative;
      z-index: 10;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      opacity: 0; /* Animated in via GSAP */
      transform: translateY(20px);
      overflow: hidden;
    }

    /* Logo Styling */
    .brand-logo {
      position: absolute;
      bottom: -20%;
      right: 35%;
      width: 360px;
      height: auto;
      margin-bottom: 24px;
      transition: all 1.65s ease;
    }


    @media (max-width:400px){ .brand-logo { right: 20%; } }
    @media (max-width:440px){ .brand-logo { right: 15%; } }
    @media (max-width:500px){ .brand-logo { right: 15%; bottom: -26%; } }
    @media (min-width:1000px){ .brand-logo { right: 40%;  } }

    /* Animated Checkmark */
    .success-svg {
      width: 80px;
      height: 80px;
      margin-bottom: 24px;
      filter: drop-shadow(0 4px 12px rgba(0,0,0,0.1));
    }

    .success-svg circle {
      fill: none;
      stroke: var(--txt-heading);
      stroke-width: 4;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .success-svg path {
      fill: none;
      stroke: var(--txt-heading);
      stroke-width: 5;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

   

    /* Primary Action Button */
    .btn-primary {
      background-color: var(--txt-heading);
      color: #ffffff;
      text-decoration: none;
      font-weight: 600;
      font-size: 16px;
      padding: 16px 36px;
      margin-top: 35px;
      border-radius: 12px;
      letter-spacing: 0.5px;
      display: inline-block;
      width: 100%;
      max-width: 300px;
      transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s;
      opacity: 0;
      cursor: pointer;
      border: none;
    }

    .btn-primary:active {
      transform: scale(0.96);
    }
    
    .btn-primary:hover {
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }

    .footer-note {
      font-size: 10px;
      font-weight: 400;
      color: #afafaf;
      margin-top: 5px;
      letter-spacing: 1px;
      text-transform: uppercase;
      opacity: 0;
    }
  </style>
</head>
<body>

  <!-- BACKGROUND PARALLAX STARS -->
  <div id="stars"></div>
  <div id="stars2"></div>
  <div id="stars3"></div>

  <!-- MAIN APP GLASS PANEL -->
  <div id="app-container" style="background: transparent!important;">
    
    <img src="https://assets.apollo.rio.br/i/apollo-s.svg" alt="Apollo::Rio" class="brand-logo" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48Y2lyY2xlIGN4PSI1MCIgY3k9IjUwIiByPSI0MCIgZmlsbD0iIzE4MTgxOCIvPjwvc3ZnPg=='" style="opacity:0">

    <svg class="success-svg" viewBox="0 0 100 100">
      <circle class="circle-path" cx="50" cy="50" r="40" />
      <path class="check-path" d="M30,52 l14,14 l26,-26" />
    </svg>

    <h1>Conta<br>Confirmada!</h1>
    <p>Sua conta foi ativada com sucesso. Você já tem acesso total à plataforma Apollo::Rio.</p>

    <button class="btn-primary" onclick="window.location.href='<?php echo esc_url( home_url( '/' ) ); ?>'">Explore agora</button>

    <div class="footer-note">apollo.rio.br</div>

  </div>

  <script>
    // Wait for DOM to be fully loaded
    document.addEventListener("DOMContentLoaded", () => {
      
      // Preparation: Hide SVG paths for drawing animation
      gsap.set(".circle-path", { strokeDasharray: 260, strokeDashoffset: 260 });
      gsap.set(".check-path", { strokeDasharray: 60, strokeDashoffset: 60 });

      // Create main animation timeline
      const tl = gsap.timeline({ defaults: { ease: "power3.out" } });

      // 1. Fade & Slide in the glass container
      tl.to("#app-container", { opacity: 1, y: 0, duration: 1, ease: "back.out(1.2)" })
        
        // 2. Fade in Logo
        .to(".brand-logo", { opacity: .05, y: 0, duration: 1.5 }, "-=0.4")

        // 3. Draw the success checkmark circle and then the check
        .to(".circle-path", { strokeDashoffset: 0, duration: 1.4, ease: "power2.inOut" }, "-=0.2")
        .to(".check-path", { strokeDashoffset: 0, duration: 1.5, ease: "back.out(1.5)" })

        // 4. Stagger fade up for text elements and button
        .to(["h1", "p", ".btn-primary", ".footer-note"], { 
          opacity: 1, 
          y: -10, // Slight upward movement for polish
          duration: 0.6, 
          stagger: 0.1 
        }, "-=0.3");

    });
  </script>
</body>
</html>