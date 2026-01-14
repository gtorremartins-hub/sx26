SELECT t1.*
FROM tlf_imoveis t1
JOIN (
    SELECT tlf_num_imovel, MIN(tlf_imoveis_id) AS id_remover
    FROM tlf_imoveis
    GROUP BY tlf_num_imovel
    HAVING COUNT(*) > 1
) t2 ON t1.tlf_num_imovel = t2.tlf_num_imovel
     AND t1.tlf_imoveis_id = t2.id_remover;

Remove Duplicados

DELETE t1
FROM tlf_imoveis t1
JOIN (
    SELECT tlf_num_imovel, MIN(tlf_imoveis_id) AS id_remover
    FROM tlf_imoveis
    GROUP BY tlf_num_imovel
    HAVING COUNT(*) > 1
) t2 ON t1.tlf_num_imovel = t2.tlf_num_imovel
     AND t1.tlf_imoveis_id = t2.id_remover;

