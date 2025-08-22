# 🚀 VPN GRATUITO PARA TU ROUTER OPENWRT

## 📦 Requisitos
- Tener OpenWRT instalado en tu Router
- Tener suficiente espacio para los paquetes de OpenVPN
- Tener buen procesamiento en tu Router para OpenVPN

## 🔧 Instalación

Asegúrate de tener instalados los siguientes paquetes de OpenWRT:
```text
openvpn-openssl
luci-app-openvpn
```
### Puedes instalarlos por CLI con estos comandos:
```text
opkg update
opkg install openvpn-openssl
opkg install luci-app-openvpn
```
### Sube las credenciales de OpenVPN a esta ruta:
```text
/etc/openvpn
```
### Crea una interfaz de Red para OpenVPN:
```text
uci set network.vpntun=interface
uci set network.vpntun.proto='none'
uci set network.vpntun.ifname='tun0'
uci commit network
```
### Crea un Firewall para el VPN:
```text
uci add firewall zone
uci set firewall.@zone[-1].name='vpn_fw'
uci set firewall.@zone[-1].input='REJECT'
uci set firewall.@zone[-1].output='ACCEPT'
uci set firewall.@zone[-1].forward='REJECT'
uci set firewall.@zone[-1].masq='1'
uci set firewall.@zone[-1].mtu_fix='1'
uci add_list firewall.@zone[-1].network='vpntun'
uci add firewall forwarding
uci set firewall.@forwarding[-1].src='lan'
uci set firewall.@forwarding[-1].dest='vpntun'
uci commit firewall
```
### Nota: *Permite la conexión en el Firewall de los LANs para tener acceso al VPN:*
![CI](https://i.imgur.com/oUYMHWg.png)

### Finalmente, sube tus archivos VPN a través de LuCi y disfruta. Para más detalles, mira mi vídeo de YouTube de cómo hacerlo.
[![Miniatura del video](https://img.youtube.com/vi/CÓDIGO_DEL_VIDEO/0.jpg)](https://www.youtube.com/watch?v=CÓDIGO_DEL_VIDEO)
